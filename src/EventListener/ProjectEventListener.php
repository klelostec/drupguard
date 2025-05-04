<?php

namespace App\EventListener;

use App\Entity\Project;
use App\Service\SchedulerUpdateService;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

#[AsEventListener(event: AfterEntityDeletedEvent::class, method: 'onEntityDeleteEvent')]
#[AsEventListener(event: AfterEntityPersistedEvent::class, method: 'onEntityPersistedEvent')]
#[AsEventListener(event: BeforeEntityUpdatedEvent::class, method: 'onEntityUpdatedEvent')]
final class ProjectEventListener
{
    private SchedulerUpdateService $schedulerUpdateService;
    private Kernel $kernel;

    public function __construct(SchedulerUpdateService $schedulerUpdateService, Kernel $kernel)
    {
        $this->schedulerUpdateService = $schedulerUpdateService;
        $this->kernel = $kernel;
    }

    protected function updateSchedulerUpdateService(AfterEntityDeletedEvent|AfterEntityPersistedEvent|BeforeEntityUpdatedEvent $event, bool $check = false)
    {
        $entity = $event->getEntityInstance();
        if (!$entity instanceof Project) {
            return;
        }
        $this->schedulerUpdateService->append($entity, $check);
    }

    public function onEntityDeleteEvent(AfterEntityDeletedEvent|AfterEntityPersistedEvent $event)
    {
        $this->updateSchedulerUpdateService($event);
        $entity = $event->getEntityInstance();
        if (!$entity instanceof Project) {
            return;
        }

        $filesystem = new Filesystem();
        if ($entity->getLogo()) {
            $logoPath = $this->kernel->getProjectDir().'public/media/projects'.$entity->getLogo();
            if ($filesystem->exists($logoPath)) {
                $filesystem->remove($logoPath);
            }
        }
        $projectRoot = $this->kernel->getProjectDir().'/workspace/'.$entity->getMachineName();
        if ($filesystem->exists($projectRoot)) {
            $filesystem->remove($projectRoot);
        }
    }

    public function onEntityPersistedEvent(AfterEntityDeletedEvent|AfterEntityPersistedEvent $event)
    {
        $this->updateSchedulerUpdateService($event);
    }

    public function onEntityUpdatedEvent(BeforeEntityUpdatedEvent $event)
    {
        $this->updateSchedulerUpdateService($event, true);
    }
}
