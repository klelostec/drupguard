<?php

namespace App\Repository;

use App\Entity\AnalyseItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    // /**
    //  * @return AnalyseItem[] Returns an array of AnalyseItem objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AnalyseItem
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
