<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;
use DataDog\PagerBundle\Pagination;
use AppBundle\Entity\Project;

class ProjectController extends Controller
{
    public function projectFilters(QueryBuilder $qb, $key, $val)
    {
        switch ($key) {
        case 'p.name':
            if ($val) {
                $qb->andWhere($qb->expr()->like('p.name', ':name'));
                $qb->setParameter('name', "%$val%");
            }
            break;
        case 'p.hoursSpent':
            switch ($val) {
            case 'lessThan10':
                $qb->andWhere($qb->expr()->lt('p.hoursSpent', 10));
                break;
            case 'upTo20':
                $qb->andWhere($qb->expr()->lte('p.hoursSpent', 20));
                break;
            case 'moreThan2weeks':
                $qb->andWhere($qb->expr()->gte('p.hoursSpent', 30));
                break;
            case 'overDeadline':
                $qb->andWhere($qb->expr()->gt('p.hoursSpent', 'p.deadline'));
                break;
            }
            break;
        case 'l.code':
            $qb->andWhere($qb->expr()->eq('l.code', ':code'));
            $qb->setParameter('code', $val);
            break;
        default:
            // if user attemps to filter by other fields, we restrict it
            throw new \Exception("filter not allowed");
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
            'filters' => ['p.hoursSpent' => 'overDeadline'], // we can apply a filter option by default
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

    /**
     * @Method("GET")
     * @Template
     * @Route("/toggle/{id}", name="project_toggle")
     */
    public function toggleAction(Project $project, Request $request)
    {
        $project->setEnabled(!$project->getEnabled());
        $em = $this->getDoctrine()->getManager();
        $em->persist($project);
        $em->flush();

        // redirect to the list with the same filters applied as before
        return $this->redirect($this->generateUrl('homepage', $request->query->all()));
    }
}
