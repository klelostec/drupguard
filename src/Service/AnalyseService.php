<?php

namespace App\Service;

use App\AnalyseLevelState;
use App\Entity\Project;
use App\Entity\Report;
use App\Plugin\Manager;
use App\Plugin\Service\Analyse;
use App\Plugin\Service\Build;
use App\Plugin\Service\Source;
use App\ProjectState;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

use function Symfony\Component\String\u;

class AnalyseService
{
    protected ContainerInterface $serviceLocator;
    protected EntityManagerInterface $entityManager;
    protected Manager $manager;
    protected LoggerInterface $logger;

    public function __construct(
        #[AutowireLocator('app.plugin.service.type')]
        ContainerInterface $serviceLocator,
        EntityManagerInterface $entityManager,
        Manager $manager,
        LoggerInterface $logger,
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->entityManager = $entityManager;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    public function process(Project $project): void
    {
        $this->logger->debug('Process project '.$project->getId());

        $report = new Report();
        $report->setDatetime(new \DateTime());

        try {
            $paths = $this->source($project);
            foreach ($paths as $path) {
                $this->build($project, $path);
                $this->analyse($project, $report, $path);
            }
        } catch (\Exception $e) {
            $report->setState(AnalyseLevelState::FAILURE);
            $report->setDetail($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logger->debug('Error during process project '.$project->getId().':'.PHP_EOL.$e->getMessage());
        }

        $this->entityManager->persist($report);
        $this->entityManager->flush();
        $project->addReport($report);
        $project->setState(ProjectState::IDLE);
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->logger->debug('End process for project '.$project->getId());
    }

    protected function source(Project $project): array
    {
        $this->logger->debug('Sourcing project '.$project->getId());
        $project->setState(ProjectState::SOURCING);
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $paths = [];
        foreach ($project->getSourcePlugins() as $plugin) {
            $this->logger->debug('Source type: '.$plugin->getType());
            $sourceEntity = $plugin->getTypeEntity();
            $classMetadata = $this->entityManager->getClassMetadata(get_class($sourceEntity));
            $typeInfo = $this->manager->getRelatedObject($classMetadata->getName());

            /**
             * @var Source $service
             */
            $service = $this->serviceLocator->get($typeInfo->getServiceClass());
            $paths[] = $service->source($project, $sourceEntity);
        }
        $this->logger->debug('End sourcing project '.$project->getId());

        return array_unique($paths);
    }

    protected function build(Project $project, string $path): void
    {
        $this->logger->debug('Building project '.$project->getId());
        $project->setState(ProjectState::BUILDING);
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        foreach ($project->getBuildPlugins() as $plugin) {
            $this->logger->debug('Build type: '.$plugin->getType());
            $buildEntity = $plugin->getTypeEntity();
            $classMetadata = $this->entityManager->getClassMetadata(get_class($buildEntity));
            $typeInfo = $this->manager->getRelatedObject($classMetadata->getName());

            /**
             * @var Build $service
             */
            $service = $this->serviceLocator->get($typeInfo->getServiceClass());
            $service->build($project, $buildEntity, $path);
        }
        $this->logger->debug('End building project '.$project->getId());
    }

    public function analyse(Project $project, Report $report, string $path): void
    {
        $this->logger->debug('Analysing project '.$project->getId());
        $project->setState(ProjectState::ANALYSING);
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $state = AnalyseLevelState::SUCCESS;
        foreach ($project->getAnalysePlugins() as $plugin) {
            $pluginType = $plugin->getType();
            $this->logger->debug('Analyse type: '.$pluginType);

            $analyseEntity = $plugin->getTypeEntity();
            $classMetadata = $this->entityManager->getClassMetadata(get_class($analyseEntity));
            $typeInfo = $this->manager->getRelatedObject($classMetadata->getName());

            /**
             * @var Analyse $service
             */
            $service = $this->serviceLocator->get($typeInfo->getServiceClass());
            $currentReport = $service->analyse($project, $analyseEntity, $path);

            $pluginState = $currentReport->getState();
            $this->entityManager->persist($currentReport);
            $this->entityManager->flush();
            $report->{'set'.mb_ucfirst(u($pluginType)->camel())}($currentReport);
            if ($pluginState->value < $state->value) {
                $state = $pluginState;
            }
        }

        $report->setState($state);
        $this->logger->debug('End analysing project '.$project->getId().' with state '.$state->name);
    }
}
