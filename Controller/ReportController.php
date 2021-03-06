<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\Controller;

use Doctrine\ORM\EntityManager;
use Loevgaard\DandomainConsignment\Entity\Report;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $paginator = $this->get('knp_paginator');

        $qb = $em->createQueryBuilder();
        $qb->select('r, s')
            ->from('Loevgaard\DandomainConsignment\Entity\Report', 'r')
            ->leftJoin('r.stockMovements', 's')
            ->addOrderBy('r.createdAt', 'desc')
        ;

        /** @var Report[] $reports */
        $reports = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 50)
        );

        return $this->render('@LoevgaardDandomainConsignment/report/index.html.twig', [
            'reports' => $reports,
        ]);
    }

    /**
     * @param Report $report
     *
     * @return Response
     */
    public function showAction(Report $report)
    {
        return $this->render('@LoevgaardDandomainConsignment/report/show.html.twig', [
            'report' => $report,
        ]);
    }
}
