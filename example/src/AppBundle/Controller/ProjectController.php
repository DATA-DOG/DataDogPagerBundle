<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use DataDog\PagerBundle\Pagination;

class ProjectController extends Controller
{
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
        ];
        $projects = new Pagination($qb, $request);
        return compact('projects');
    }
}
