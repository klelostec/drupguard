<?php

namespace App\MessageHandler;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class ProjectAnalyseHandlerAbstract
{
    protected EntityManagerInterface $entityManager;
    protected EntityRepository $repository;
    protected MessageBusInterface $bus;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Project::class);
        $this->bus = $bus;
    }
}
