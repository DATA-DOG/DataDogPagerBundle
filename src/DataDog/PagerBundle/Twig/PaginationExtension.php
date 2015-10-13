<?php

namespace DataDog\PagerBundle\Twig;

use Symfony\Component\Routing\RouterInterface;
use DataDog\PagerBundle\Pagination;

class PaginationExtension extends \Twig_Extension
{
    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $defaults = [
            'is_safe' => ['html'],
            'needs_environment' => true,
        ];

        return [
            new \Twig_SimpleFunction('filter_uri', [$this, 'filterUri']),
            new \Twig_SimpleFunction('filter_is_active', [$this, 'filterIsActive']),

            new \Twig_SimpleFunction('filter_select', [$this, 'filterSelect'], $defaults),
            new \Twig_SimpleFunction('filter_search', [$this, 'filterSearch'], $defaults),
            new \Twig_SimpleFunction('filter_dropdown', [$this, 'filterDropdown'], $defaults),

            new \Twig_SimpleFunction('sorter_link', [$this, 'sorterLink'], $defaults),

            new \Twig_SimpleFunction('pagination', [$this, 'pagination'], $defaults),
        ];
    }

    protected function mergeRecursive(array $a, array $b)
    {
        foreach ($b as $k => $v) {
            if (!array_key_exists($k, $a)) {
                $a[$k] = $v;
                continue;
            }
            if (is_array($v)) {
                $a[$k] = $this->mergeRecursive(is_array($a[$k]) ? $a[$k] : [], $v);
                continue;
            }
            $a[$k] = $v;
        }
        return $a;
    }

    public function sorterLink(\Twig_Environment $twig, Pagination $pagination, $key, $title)
    {
        $params = $pagination->query();
        $direction = 'asc';
        $className = "";
        if (isset($params['sorters'][$key])) {
            $direction = strtolower($params['sorters'][$key]) === 'asc' ? 'desc' : 'asc';
            $className = $direction;
        }
        // @NOTE: here multiple sorters can be used if sorters are merged from parameters
        // but not overwritten
        $uri = $this->router->generate($pagination->route(), array_merge($pagination->query(), [
            'sorters' => [$key => $direction],
            'page' => 1,
        ]));

        return $twig->render(
            'DataDogPagerBundle::sorters/link.html.twig',
            compact('key', 'pagination', 'title', 'uri', 'direction', 'className')
        );
    }

    public function filterSelect(\Twig_Environment $twig, Pagination $pagination, $key, array $options)
    {
        return $twig->render('DataDogPagerBundle::filters/select.html.twig', compact('key', 'pagination', 'options'));
    }

    public function filterDropdown(\Twig_Environment $twig, Pagination $pagination, $key, array $options)
    {
        $default = reset($options);
        return $twig->render('DataDogPagerBundle::filters/dropdown.html.twig', compact('key', 'pagination', 'options', 'default'));
    }

    public function filterSearch(\Twig_Environment $twig, Pagination $pagination, $key, $placeholder = '')
    {
        $value = isset($pagination->query()['filters'][$key]) ? $pagination->query()['filters'][$key] : '';
        return $twig->render('DataDogPagerBundle::filters/search.html.twig', compact('key', 'pagination', 'value', 'placeholder'));
    }

    public function filterUri(Pagination $pagination, $key, $value)
    {
        return $this->router->generate($pagination->route(), $this->mergeRecursive($pagination->query(), [
            'filters' => [$key => $value],
            'page' => 1,
        ]));
    }

    public function filterIsActive(Pagination $pagination, $key, $value)
    {
        return isset($pagination->query()['filters'][$key]) && $pagination->query()['filters'][$key] == $value;
    }

    public function pagination(\Twig_Environment $twig, Pagination $pagination)
    {
        return $twig->render('DataDogPagerBundle::pagination.html.twig', compact('pagination'));
    }

    public function getName()
    {
        return 'datadog_pagination_extension';
    }
}
