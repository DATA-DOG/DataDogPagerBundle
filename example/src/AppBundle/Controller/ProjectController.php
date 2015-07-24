<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use DataDog\PagerBundle\Pagination;

class ProjectController extends Controller
{
    public function projectFilters(QueryBuilder $qb, $key, $val)
    {
        switch ($key) {
        case 'p.name':
            if ($val) {
                $qb->andWhere($qb->expr()->like('p.name', "'%{$val}%'"));
            } else {
                $qb->andWhere($qb->expr()->isNotNull('p.name'));
            }
            break;
        case 'p.hoursSpent':
            switch ($val) {
            case 'lessThan10':
                $qb->andWhere($qb->expr()->lt('p.hoursSpent', $qb->expr()->literal(10)));
                break;
            case 'upTo20':
                $qb->andWhere($qb->expr()->lte('p.hoursSpent', $qb->expr()->literal(20)));
                break;
            case 'moreThan2weeks':
                $qb->andWhere($qb->expr()->gte('p.hoursSpent', $qb->expr()->literal(80)));
                break;
            case 'overDeadline':
                $qb->andWhere($qb->expr()->gt('p.hoursSpent', 'p.deadline'));
                break;
            }
            break;
        }
    }

    /**
     * @Method("GET")
     * @Template
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $qb = $this->getDoctrine()->getManager()->getRepository("AppBundle:Project")
            ->createQueryBuilder('p')
            ->addSelect('l')
            ->innerJoin('p.language', 'l');

        $options = [
            'sorters' => ['l.code' => 'ASC'], // sorted by language code by default
            'applyFilter' => [$this, 'projectFilters'], // custom filter handling
        ];

        $languages = [
            Pagination::$filterAny => 'Any',
            'php' => 'PHP',
            'hs' => 'Haskell',
            'go' => 'Golang',
        ];

        $spentTimeGroups = [
            Pagination::$filterAny => 'Any',
            'lessThan10' => 'Less than 10h',
            'upTo20' => 'Up to 20h',
            'moreThan2weeks' => 'More than 2weeks',
            'overDeadline' => 'Over deadline',
        ];

        $projects = new Pagination($qb, $request, $options);
        return compact('projects', 'languages', 'spentTimeGroups');
    }
}
