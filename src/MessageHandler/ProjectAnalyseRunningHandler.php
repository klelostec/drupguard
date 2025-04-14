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
            fwrite(STDOUT, "==== No project ID provided for running analysis\n");
            return;
        }
    
        fwrite(STDOUT, "==== Searching for project ID " . $message->getProjectId() . "\n");
        $project = $this->repository->find($message->getProjectId());
    
        if (!$project || $project->getState() !== ProjectState::PENDING) {
            fwrite(STDOUT, "==== PROJECT NOT FOUND or STATE IS NOT PENDING for ID " . $message->getProjectId() . "\n");
            return;
        }
    
        fwrite(STDOUT, "==== Starting analysis for project ID " . $project->getId() . "\n");
    
        // Appel à l'AnalyseService
        $this->analyseService->process($project);
    
        fwrite(STDOUT, "==== Analysis completed for project ID " . $project->getId() . "\n");
    }
    
}