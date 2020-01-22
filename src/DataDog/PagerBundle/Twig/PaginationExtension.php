<?php

namespace DataDog\PagerBundle\Twig;

use Symfony\Component\Routing\RouterInterface;
use DataDog\PagerBundle\Pagination;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PaginationExtension extends AbstractExtension
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
            new TwigFunction('filter_uri', [$this, 'filterUri']),
            new TwigFunction('filter_is_active', [$this, 'filterIsActive']),

            new TwigFunction('filter_select', [$this, 'filterSelect'], $defaults),
            new TwigFunction('filter_search', [$this, 'filterSearch'], $defaults),
            new TwigFunction('filter_dropdown', [$this, 'filterDropdown'], $defaults),

            new TwigFunction('sorter_link', [$this, 'sorterLink'], $defaults),

            new TwigFunction('pagination', [$this, 'pagination'], $defaults),
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

    public function sorterLink(Environment $twig, Pagination $pagination, $key, $title)
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
            '@DataDogPager/sorters/link.html.twig',
            compact('key', 'pagination', 'title', 'uri', 'direction', 'className')
        );
    }

    public function filterSelect(Environment $twig, Pagination $pagination, $key, array $options)
    {
        return $twig->render('@DataDogPager/filters/select.html.twig', compact('key', 'pagination', 'options'));
    }

    public function filterDropdown(Environment $twig, Pagination $pagination, $key, array $options)
    {
        $default = reset($options);
        return $twig->render('@DataDogPager/filters/dropdown.html.twig', compact('key', 'pagination', 'options', 'default'));
    }

    public function filterSearch(Environment $twig, Pagination $pagination, $key, $placeholder = '')
    {
        $value = isset($pagination->query()['filters'][$key]) ? $pagination->query()['filters'][$key] : '';
        return $twig->render('@DataDogPager/filters/search.html.twig', compact('key', 'pagination', 'value', 'placeholder'));
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

    public function pagination(Environment $twig, Pagination $pagination)
    {
        return $twig->render('@DataDogPager/pagination.html.twig', compact('pagination'));
    }

    public function getName()
    {
        return 'datadog_pagination_extension';
    }
}
