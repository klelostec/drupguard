<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
     * @param \App\Entity\User $user
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    protected function getQueryByAllowedUser(User $user)
    {
        $query = $this->createQueryBuilder('p')
          ->leftJoin('p.allowedUsers', 'pu', Join::WITH)
          ->andWhere('p.isPublic = 1 OR 1 = :user OR p.owner = :user OR (pu.id IS NOT NULL AND pu.id = :user)')
          ->setParameter('user', $user->getId())
          ->orderBy('p.name', 'ASC')
          ->groupBy('p.id');

        return new Paginator($query);
    }

    /**
     * @return Project[] Returns an array of Project objects
     */
    public function findAllByAllowedUser(User $user)
    {
        return $this->getQueryByAllowedUser($user)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Project[] Returns an array of Project objects
     */
    public function findByAllowedUser(User $user, $page = 0, $limit = 10)
    {
        return $this->getQueryByAllowedUser($user)
            ->getQuery()
            ->setMaxResults($limit)
            ->setFirstResult(($page ?: 0)*$limit)
            ->getResult()
            ;
    }

    /**
     * @return int Returns the number of Project objects
     */
    public function countByAllowedUser(User $user)
    {
        return $this->getQueryByAllowedUser($user)->count();
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

    /**
     * @return Project[] Returns an array of Project objects
     */
    public function findByQueue($numberItems = 10)
    {
        $query = $this->createQueryBuilder('p')
          ->orderBy('p.analyseQueue', 'ASC')
          ->andWhere('p.analyseQueue IS NOT NULL')
          ->setMaxResults($numberItems);
        return $query->getQuery()->getResult();
    }
}
