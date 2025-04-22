<?php

namespace App\MessageHandler;

use App\Message\SchedulerUpdate;
use App\Scheduler\ProjectAnalysePendingSchedule;
use App\Service\SchedulerUpdateService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SchedulerUpdateHandler
{
    private readonly ProjectAnalysePendingSchedule $projectAnalysePendingSchedule;
    private SchedulerUpdateService $schedulerUpdateService;

    public function __construct(
        ProjectAnalysePendingSchedule $projectAnalysePendingSchedule,
        SchedulerUpdateService $schedulerUpdateService,
    ) {
        $this->projectAnalysePendingSchedule = $projectAnalysePendingSchedule;
        $this->schedulerUpdateService = $schedulerUpdateService;
    }

    public function __invoke(SchedulerUpdate $message)
    {
        $this->projectAnalysePendingSchedule->updateMessages();
    }
}
