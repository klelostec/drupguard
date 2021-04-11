<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @return Project[] Returns an array of Project objects
     */
    public function findByAllowedUser(User $user)
    {
        return $this->createQueryBuilder('p')
          ->leftJoin('p.allowedUsers', 'pu', Join::WITH)
          ->andWhere('(pu.id IS NULL AND p.owner = :user) OR pu.id = :user OR p.isPublic = 1')
          ->setParameter('user', $user->getId())
          ->orderBy('p.id', 'ASC')
          ->setMaxResults(10)
          ->getQuery()
          ->getResult()
          ;
    }

    /**
     * @return Project[] Returns an array of Project objects
     */
    public function findByCronNeeded($onlyCron = true)
    {
        $query = $this->createQueryBuilder('p')
          ->leftJoin('p.lastAnalyse', 'pla', Join::LEFT_JOIN)
          ->andWhere('(p.hasCron = 1 OR p.hasCron = :onlyCron) AND (pla.id IS NULL OR pla.isRunning = 0)')
          ->setParameter('onlyCron', boolval($onlyCron) ? 1 : 0)
          ->orderBy('p.id', 'ASC');
        return $query->getQuery()->getResult();
    }



}
