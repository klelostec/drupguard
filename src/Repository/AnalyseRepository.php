<?php

namespace App\Repository;

use App\Entity\Analyse;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Analyse|null find($id, $lockMode = null, $lockVersion = null)
 * @method Analyse|null findOneBy(array $criteria, array $orderBy = null)
 * @method Analyse[]    findAll()
 * @method Analyse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnalyseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Analyse::class);
    }

    protected function getQueryByProject(Project $project): Paginator
    {
        $query = $this->createQueryBuilder('a')
            ->andWhere('a.project = :project')
            ->setParameter('project', $project->getId())
            ->orderBy('a.date', 'DESC');
        return new Paginator($query);
    }

    /**
     * @return Analyse[] Returns an array of Analyse objects
     */
    public function findByProject(Project $project, $page = 0, $limit = 10)
    {
        return $this->getQueryByProject($project)
            ->getQuery()
            ->setMaxResults($limit)
            ->setFirstResult(($page ?: 0)*$limit)
            ->getResult()
        ;
    }

    /**
     * @return int Returns the number of Project objects
     */
    public function countByProject(Project $project)
    {
        return $this->getQueryByProject($project)->count();
    }

    protected function getPreviousNextAnalyse(Analyse $analyse, $compare = '<')
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isRunning = 0 AND a.project = :project AND a.date ' . $compare . ' :analyse')
            ->setParameter('project', $analyse->getProject()->getId())
            ->setParameter('analyse', $analyse->getDate())
            ->orderBy('a.date', $compare === '<' ? 'DESC' : 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function getNextAnalyse(Analyse $analyse)
    {
        return $this->getPreviousNextAnalyse($analyse, '>');
    }

    public function getPreviousAnalyse(Analyse $analyse)
    {
        return $this->getPreviousNextAnalyse($analyse, '<');
    }
}
