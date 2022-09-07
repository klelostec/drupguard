<?php

namespace App\Repository;

use App\Entity\AnalyseItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnalyseItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnalyseItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnalyseItem[]    findAll()
 * @method AnalyseItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnalyseItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnalyseItem::class);
    }

    public function findAllStatistics() {
        $query = $this->createQueryBuilder('ai');
        $subquery = $this->createQueryBuilder('ai2');
        $subquery
            ->select('MAX(a2.id)')
            ->join('ai2.analyse','a2')
            ->groupBy('a2.project');

        $query
            ->join('ai.analyse', 'a', Join::WITH)
            ->join('a.project', 'p', Join::WITH)
            ->where('a.id IN (' . $subquery->getDQL() . ')')
            ->andWhere('ai.isIgnored = 0')
            ->orderBy('ai.type')
            ->addOrderBy('ai.name')
            ->addOrderBy('p.name')
        ;
        return $query->getQuery()->getResult();
    }
}
