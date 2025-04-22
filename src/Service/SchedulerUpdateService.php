<?php

namespace App\Service;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;

class SchedulerUpdateService
{
    private DoctrineDbalAdapter $cache;
    private EntityManagerInterface $em;
    private MarshallerInterface $marshaller;

    public function __construct(EntityManagerInterface $em, string $dsn)
    {
        $this->marshaller = new DefaultMarshaller();
        $this->em = $em;
        $this->cache = new DoctrineDbalAdapter(
            $dsn,
            '',
            0,
            [
                'db_table' => 'schedule_update_items',
            ]
        );
    }

    public function append(Project $entity, $check = false)
    {
        if ($check) {
            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSets();
            $changeset = $uow->getEntityChangeSet($entity);
            if (empty($changeset['periodicity'])) {
                return;
            }
        }

        $item = $this->cache->getItem('projects');
        $projects = $item->get() ?? [];
        $projects[] = $entity->getId();
        $item->set($projects);
        $this->cache->save($item);
    }

    public function getAll(): iterable
    {
        $item = $this->cache->getItem('projects');

        return $item->get() ?? [];
    }

    public function clear(): void
    {
        $item = $this->cache->getItem('projects');
        $item->set([]);
        $this->cache->save($item);
    }
}
