<?php

namespace App\MessageHandler;

use App\Entity\Project;
use App\Message\ProjectAnalyseAbstract;
use App\Message\ProjectAnalysePending;
use App\Message\ProjectAnalyseRunning;
use App\ProjectState;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
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