<?php

namespace App\MessageHandler;

use App\Entity\Project;
use App\Message\ProjectAnalyseRunning;
use App\Service\AnalyseService;
use App\ProjectState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ProjectAnalyseRunningHandler extends ProjectAnalyseHandlerAbstract
{
    protected AnalyseService $analyseService;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $bus, AnalyseService $analyseService)
    {
        parent::__construct($entityManager, $bus);
        $this->analyseService = $analyseService;
    }

    public function __invoke(ProjectAnalyseRunning $message)
    {
        if (empty($message->getProjectId())) {
            return;
        }
    
        $project = $this->repository->find($message->getProjectId());
    
        if (!$project || $project->getState() !== ProjectState::PENDING) {
            return;
        }
    
        $this->analyseService->process($project);
    }
    
}