<?php

namespace App\Service;

use App\Entity\Analyse;
use App\Entity\AnalyseItem;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpKernel\KernelInterface;

class StatsHelper {

    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $kernel)
    {
        $this->entityManager = $entityManager;
        $this->entityManager->getConfiguration()->addCustomHydrationMode('COLUMN_HYDRATOR', 'App\Doctrine\ORM\Hydration\ColumnHydrator');
    }

    protected function getLastFinishedAnalyse(Project $project) {
        $query = $this->entityManager->createQueryBuilder()
          ->select('a')
          ->from(Analyse::class, 'a')
          ->where('a.project = :project AND a.isRunning=0')
          ->orderBy('a.date', 'DESC')
          ->setMaxResults(1)
          ->setParameter(':project', $project->getId());

        return $query->getQuery()->getOneOrNullResult();
    }

    function buildProjectDonut(Project $project) {
        if(!($analyse = $this->getLastFinishedAnalyse($project))) {
            return [];
        }

        $query = $this->entityManager->createQueryBuilder()
          ->select([
            "CASE WHEN ai.state = 1 THEN 'danger' WHEN ai.state IN (2, 3, 4) THEN 'warning' WHEN ai.state = 5 THEN 'success' ELSE 'other' END as state",
            "COUNT(ai) as count"
          ])
          ->from(AnalyseItem::class, 'ai')
          ->groupBy('ai.state')
          ->where('ai.analyse = :analyse')
          ->setParameter(':analyse', $analyse->getId());

        $ret = $query->getQuery()->getResult('COLUMN_HYDRATOR');
        return $ret;
    }

    function buildProjectHistory(Project $project) {
        if(!($analyse = $this->getLastFinishedAnalyse($project))) {
            return [];
        }

        $query = $this->entityManager->createQueryBuilder()
          ->select([
            "a.id",
            "a.date",
            "SUM(CASE WHEN ai.state = 1 THEN 1 ELSE 0 END) as danger",
            "SUM(CASE WHEN ai.state IN (2, 3, 4) THEN 1 ELSE 0 END) as warning",
            "SUM(CASE WHEN ai.state = 5 THEN 1 ELSE 0 END) as success",
            "SUM(CASE WHEN ai.state NOT IN (1, 2, 3, 4, 5) THEN 1 ELSE 0 END) as other"
          ])
          ->from(AnalyseItem::class, 'ai')
          ->join('ai.analyse', 'a', Join::WITH)
          ->groupBy('a.id')
          ->where('a.project = :project AND a.isRunning=0')
          ->setParameter(':project', $project->getId())
          ->setMaxResults(12)
          ->setFirstResult(0)
          ->orderBy('a.date', 'DESC')
        ;


        $ret = [
          'data' => [
            ['success'],
            ['warning'],
            ['other'],
            ['danger']
          ],
          'categories' => []
        ];
        if(!empty($res = $query->getQuery()->getResult())) {
            $res = array_reverse($res);
            foreach($res as $current) {
                $ret['data'][0][] = $current['success'];
                $ret['data'][1][] = $current['warning'];
                $ret['data'][2][] = $current['other'];
                $ret['data'][3][] = $current['danger'];
                $ret['categories'][] = $current["date"]->format('d/m/Y H:i:s');
            }
        }

        return $ret;
    }


}