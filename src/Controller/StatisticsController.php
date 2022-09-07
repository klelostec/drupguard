<?php

namespace App\Controller;

use App\Repository\AnalyseItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/statistics")
 */
class StatisticsController extends AbstractController
{
    /**
     * @Route("/", name="statistics_index", methods={"GET"})
     */
    public function index(AnalyseItemRepository $analyseItemRepository): Response
    {
        $statistics = $analyseItemRepository->findAllStatistics();
        return $this->render('statistics/index.html.twig', [
            'statistics' => $statistics
        ]);
    }
}
