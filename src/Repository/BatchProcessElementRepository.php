<?php

namespace App\Repository;

use App\Entity\BatchProcessElement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BatchProcessElement>
 *
 * @method BatchProcessElement|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatchProcessElement|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatchProcessElement[]    findAll()
 * @method BatchProcessElement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatchProcessElementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchProcessElement::class);
    }

    public function save(BatchProcessElement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BatchProcessElement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return BatchProcessElement[] Returns an array of BatchProcessElement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BatchProcessElement
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
