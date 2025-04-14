<?php

namespace App\MessageHandler;

use App\Message\ProjectAnalysePending;
use App\Message\ProjectAnalyseRunning;
use App\ProjectState;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

#[AsMessageHandler]
class ProjectAnalysePendingHandler extends ProjectAnalyseHandlerAbstract {

    public function __invoke(ProjectAnalysePending $message)
    {
        if (empty($message->getProjectId())) {
            fwrite(STDOUT, "==== NO PROJECT ID PROVIDED \n");
            return;
        }

        $project = $this->repository->find($message->getProjectId());

        if (!$project) {
            fwrite(STDOUT, "==== PROJECT NOT FOUND for ID+++++++++++ " . $message->getProjectId() . "\n");
            return;
        }

        if ($project->getState() !== ProjectState::IDLE) {
            fwrite(STDOUT, "==== PROJECT STATE IS NOT IDLE, it is: " . $project->getState()->name . "\n");
            return; 
        }

        $projectState = $project->getState();
        fwrite(STDOUT, "==== STATE BEFORE UPDATE +++++++++++++++++ " . $projectState->name . "\n");

        fwrite(STDOUT, "==== LOG DEBUG TEST for project " . $project->getId() . "\n");

        $project->setState(ProjectState::PENDING);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        
        fwrite(STDOUT, "==== STATE AFTER UPDATE +++++++++++++++++ " . $project->getState()->name . "\n");

        fwrite(STDOUT, "==== Dispatching ProjectAnalyseRunning message for project ID: " . $project->getId() . "\n");

        $event = new ProjectAnalyseRunning($project->getId());
        $this->bus->dispatch(

            (new Envelope($event))->with(new DispatchAfterCurrentBusStamp())
        );
    }
}
