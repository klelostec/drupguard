<?php

namespace App\Scheduler;

use App\Entity\Project;
use App\Message\ProjectAnalysePending;
use App\Message\SchedulerUpdate;
use App\ProjectState;
use App\Service\SchedulerUpdateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Event\PreRunEvent;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('default')]
#[AsEventListener(PreRunEvent::class, 'onMessage')]
final class ProjectAnalysePendingSchedule implements ScheduleProviderInterface
{
    private EntityManagerInterface $em;

    /** @var RecurringMessage[] */
    private array $projectMessageMapper = [];

    private ?Schedule $schedule = null;
    private SchedulerUpdateService $schedulerUpdateService;

    public function __construct(
        SchedulerUpdateService $schedulerUpdateService,
        EntityManagerInterface $em,
    ) {
        $this->schedulerUpdateService = $schedulerUpdateService;
        $this->em = $em;
    }

    public function getSchedule(): Schedule
    {
        if ($this->schedule) {
            return $this->schedule;
        }
        $this->schedulerUpdateService->clear();
        $projectsQuery = $this->em
            ->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->andWhere('p.periodicity IS NOT NULL and p.periodicity <> \'\'')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($projectsQuery as $project) {
            $this->projectMessageMapper[$project->getId()] = RecurringMessage::cron(
                $project->getPeriodicity(),
                new RedispatchMessage(new ProjectAnalysePending($project->getId()), 'analyse_low')
            );
        }

        return $this->schedule ??= (new Schedule())
            ->with(
                RecurringMessage::cron('* * * * *', new SchedulerUpdate()),
                ...$this->projectMessageMapper
            );
    }

    public function onMessage(PreRunEvent $event): void
    {
        $message = $event->getMessage();
        if (
            !($message instanceof RedispatchMessage)
            || !($message->envelope->getMessage() instanceof ProjectAnalysePending)
            || empty($message->envelope->getMessage()->getProjectId())
        ) {
            return;
        }

        $project = $this->em
            ->getRepository(Project::class)
            ->find($message->envelope->getMessage()->getProjectId());
        if (!$project || ProjectState::IDLE !== $project->getState()) {
            $event->shouldCancel(true);
        }
    }

    public function updateMessages(): void
    {
        $items = $this->schedulerUpdateService->getAll();
        if (!empty($items)) {
            foreach ($items as $projectId) {
                if (!empty($this->projectMessageMapper[$projectId])) {
                    $this->schedule->removeById($this->projectMessageMapper[$projectId]->getId());
                    unset($this->projectMessageMapper[$projectId]);
                }
                $project = $this->em
                    ->getRepository(Project::class)
                    ->find($projectId);
                if (!$project || empty($project->getPeriodicity())) {
                    continue;
                }
                $message = RecurringMessage::cron(
                    $project->getPeriodicity(),
                    new RedispatchMessage(new ProjectAnalysePending($project->getId()), 'analyse_low')
                );
                $this->projectMessageMapper[$project->getId()] = $message;
                $this->schedule->add($message);
            }
            $this->schedulerUpdateService->clear();
        }
    }
}
