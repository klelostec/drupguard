<?php

namespace App\MessageHandler;

use App\Message\ProjectAnalyseRunning;
use App\ProjectState;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProjectAnalyseRunningHandler extends ProjectAnalyseHandlerAbstract
{

    public function __invoke(ProjectAnalyseRunning $message)
    {
        if (empty($message->getProjectId())) {
            return;
        }

        $project = $this->repository->find($message->getProjectId());
        if (!$project || $project->getState() !== ProjectState::PENDING) {
            return;
        }

        $project->setState(ProjectState::SOURCING);
        $this->entityManager->persist($project);
    }
}