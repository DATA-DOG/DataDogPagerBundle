<?php

namespace DataDog\PagerBundle;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class Pagination extends \ArrayIterator
{
    /**
     * a default filter value for ANY filter option
     * does not modify a query with this filter value.
     * Use this value in filter select option
     *
     * @var string
     */
    public static $filterAny = 'any';

    /**
     * this is an upper bound for max items per page
     * if an user modifiers request uri limit to a crazy number
     * that may impact the server performance. This number
     * will ensure the limit
     *
     * @var integer
     */
    public static $maxPerPage = 500;

    /**
     * Default pager options
     *
     * @var array
     */
    public static $defaults = [
        /**
         * This callable will be called for every filter option
         * found in the url: function (QueryBuilder $qb, $filterKey, $filterValue)
         *
         * The Pager checks whether the DQL has changed after calling
         * handler. And if it did, it skips the default handling.
         *
         * You may throw the exception if this filter is not allowed.
         *
         * @var callable
         */
        'applyFilter' => null,

        /**
         * This callable will be called for every sorter option
         * found in the url: function (QueryBuilder $qb, $sorterKey, $direction)
         *
         * The Pager checks whether the DQL has changed after calling
         * handler. And if it did, it skips the default handling.
         *
         * You may throw the exception if this sorter is not allowed.
         *
         * @var callable
         */
        'applySorter' => null,

        /**
         * Default filters to apply ['key' => 'value'] array
         *
         * @var array
         */
        'filters' => [], // default filters to apply

        /**
         * Default sorters to apply ['key' => 'direction'] array
         *
         * @var array
         */
        'sorters' => [], // default sorters to apply

        /**
         * Page range for pagination
         *
         * @var integer
         */
        'range' => 10,

        /**
         * Number of items per page
         *
         * @var integer
         */
        'limit' => 10,
    ];

    /**
     * Pagination data values
     *
     * @var array
     */
    protected $pagination;

    /**
     * Request query parameters
     *
     * @var array
     */
    protected $query;

    /**
     * Currently used route name
     *
     * @var string
     */
    protected $route;

    /**
     * Total item count
     *
     * @var integer
     */
    protected $count;

    /**
     * items per page
     *
     * @var integer
     */
    protected $limit;

    /**
     * Current page
     *
     * @var integer
     */
    protected $page;

    /**
     * Paginate given $qb based on $request
     * accepts $options for customization for filters and sorters
     *
     * @param QueryBuilder $qb
     * @param Request $request
     * @param array $options
     * @return Pager
     */
    public function __construct(QueryBuilder $qb, Request $request, array $options = [])
    {
        extract(array_merge(self::$defaults, $options));

        $params = array_merge($request->query->all(), $request->attributes->all());
        foreach ($params as $key => $param) {
            if (substr($key, 0, 1) == '_') {
                unset($params[$key]);
            }
        }
        // merge default sorters
        $params['sorters'] = array_merge($sorters, isset($params['sorters']) ? $params['sorters'] : []);
        // merge default filters
        $params['filters'] = array_merge($filters, isset($params['filters']) ? $params['filters'] : []);

        $paginator = clone $qb;
        $this->applyFilters($paginator, $params['filters'], $applyFilter);
        $this->applySorters($paginator, $params['sorters'], $applySorter);

        $counter = clone $paginator;
        $counter->resetDQLPart('orderBy');
        $counter->select("COUNT({$counter->getRootAlias()})");

        $this->page = max(abs(intval((isset($params['page']) ? $params['page'] : 1))), 1);
        $this->limit = abs(intval((isset($params['limit']) ? $params['limit'] : $limit)));
        // ensure upper bound
        $this->limit = min($this->limit, self::$maxPerPage);

        $this->count = intval($counter->getQuery()->getSingleScalarResult());

        // Set page to last one if query is more than total
        $this->page = max(min(intval(ceil($this->count / $this->limit)), $this->page), 1);

        $paginator->setFirstResult(($this->page - 1) * $this->limit);
        $paginator->setMaxResults($this->limit);

        $this->route = $request->attributes->get('_route');
        $this->query = $params;

        $this->pagination = $this->buildPagination($this->page, $range);

        parent::__construct($paginator->getQuery()->getResult());
    }

    /**
     * @return int
     */
    public function currentPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function itemsPerPage()
    {
        return $this->limit;
    }

    public function query()
    {
        return $this->query;
    }

    public function route()
    {
        return $this->route;
    }

    public function total()
    {
        return $this->count;
    }

    public function pagination()
    {
        return $this->pagination;
    }

    protected function applySorters(QueryBuilder $qb, array $sorters, callable $handler = null)
    {
        foreach ($sorters as $key => $direction) {
            // custom handling
            if (null !== $handler) {
                $dql = $qb->getDQL(); // will check for difference
                call_user_func_array($handler, [$qb, $key, $direction]);
                if ($qb->getDQL() !== $dql) {
                    return; // custom sorter handler has handled the parameter
                }
            }
            $qb->addOrderBy($key, in_array(strtoupper($direction), ['ASC', 'DESC']) ? $direction : 'ASC');
        }
    }

    protected function applyFilters(QueryBuilder $qb, array $filters, callable $handler = null)
    {
        foreach ($filters as $key => $val) {
            if ($val === self::$filterAny) {
                return; // any value is accepted for this filter
            }
            // custom handling
            if (null !== $handler) {
                $dql = $qb->getDQL(); // will check for difference
                call_user_func_array($handler, [$qb, $key, $val]);
                if ($qb->getDQL() !== $dql) {
                    return; // custom filter handler has handled the parameter
                }
            }
            $name = str_replace('.', '_', $key);
            $qb->andWhere($qb->expr()->{is_array($val) ? 'in' : 'eq'}($key, ':'.$name));
            $qb->setParameter($name, $val);
        }
    }

    protected function buildPagination($page, $range)
    {
        $pageCount = intval(ceil($this->total() / $this->limit));
        $current = $page;
        if ($range > $pageCount) {
            $range = $pageCount;
        }
        $delta = ceil($range / 2);
        if ($current - $delta > $pageCount - $range) {
            $pages = range($pageCount - $range + 1, max($pageCount, 1));
        } else {
            if ($current - $delta < 0) {
                $delta = $current;
            }
            $offset = $current - $delta;
            $pages = range($offset + 1, $offset + $range);
        }

        $proximity = floor($range / 2);
        $startPage = $current - $proximity;
        $endPage = $current + $proximity;
        if ($startPage < 1) {
            $endPage = min($endPage + (1 - $startPage), $pageCount);
            $startPage = 1;
        }
        if ($endPage > $pageCount) {
            $startPage = max($startPage - ($endPage - $pageCount), 1);
            $endPage = $pageCount;
        }

        $viewData = [
            'last' => $pageCount,
            'current' => $current,
            'numItemsPerPage' => $this->limit,
            'first' => 1,
            'pageCount' => $pageCount,
            'totalCount' => $this->total(),
            'pageRange' => $range,
            'startPage' => $startPage,
            'endPage' => $endPage,
        ];

        if ($current - 1 > 0) {
            $viewData['previous'] = $current - 1;
        }
        if ($current + 1 <= $pageCount) {
            $viewData['next'] = $current + 1;
        }

        $viewData['pagesInRange'] = $pages;
        $viewData['firstPageInRange'] = min($pages);
        $viewData['lastPageInRange']  = max($pages);
        $viewData['currentItemCount'] = $this->count();
        $viewData['firstItemNumber'] = (($current - 1) * $this->limit) + 1;
        $viewData['lastItemNumber'] = $viewData['firstItemNumber'] + $viewData['currentItemCount'] - 1;
        return $viewData;
    }
}
