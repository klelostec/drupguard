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
use Symfony\Component\VarDumper\VarDumper;
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
        LoggerInterface $logger
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->entityManager = $entityManager;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    public function process(Project $project): void {
        $this->logger->debug('==== Analyse start for project ' . $project->getId());
        fwrite(STDOUT, "==== Analyse start for project ID " . $project->getId() . "\n");
    
        $report = new Report();
        $report->setDatetime(new \DateTime());
    
        try {
            fwrite(STDOUT, "==== Starting sourcing for project ID " . $project->getId() . "\n");
            $paths = $this->source($project);
            fwrite(STDOUT, "==== Sourcing completed for project ID " . $project->getId() . "\n");
    
            foreach ($paths as $path) {
                fwrite(STDOUT, "==== Starting build for path: " . $path . "\n");
                $this->build($project, $path);
                fwrite(STDOUT, "==== Build completed for path: " . $path . "\n");
    
                fwrite(STDOUT, "==== Starting analysis for path: " . $path . "\n");
                $this->analyse($project, $report, $path);
                fwrite(STDOUT, "==== Analysis completed for path: " . $path . "\n");
            }
        } catch (\Exception $e) {
            $report->setState(AnalyseLevelState::FAILURE);
            $report->setDetail($e->getMessage());
            fwrite(STDOUT, "==== ERROR during analysis: " . $e->getMessage() . "\n");
        }
    
        $this->entityManager->persist($report);
        $this->entityManager->flush();
        $project->addReport($report);
        $project->setState(ProjectState::IDLE);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    
        $this->logger->debug('==== Analyse end for project ' . $project->getId());
        fwrite(STDOUT, "==== Analyse end for project ID " . $project->getId() . "\n");
    }
    

    protected function source(Project $project): array {
        $project->setState(ProjectState::SOURCING);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        $this->logger->debug('Sourcing project ' . $project->getId());

        $paths = [];
        foreach ($project->getSourcePlugins() as $plugin) {
            $sourceEntity = $plugin->getTypeEntity();
            $classMetadata = $this->entityManager->getClassMetadata(get_class($sourceEntity));
            $typeInfo = $this->manager->getRelatedObject($classMetadata->getName());

            /**
             * @var Source $service
             */
            $service = $this->serviceLocator->get($typeInfo->getServiceClass());
            $paths[] = $service->source($project, $sourceEntity);
        }

        return array_unique($paths);
    }

    protected function build(Project $project, string $path): void
    {
        $project->setState(ProjectState::BUILDING);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        $this->logger->debug('Building project ' . $project->getId());

        foreach ($project->getBuildPlugins() as $plugin) {
            $buildEntity = $plugin->getTypeEntity();
            $classMetadata = $this->entityManager->getClassMetadata(get_class($buildEntity));
            $typeInfo = $this->manager->getRelatedObject($classMetadata->getName());

            /**
             * @var Build $service
             */
            $service = $this->serviceLocator->get($typeInfo->getServiceClass());
            $service->build($project, $buildEntity, $path);
        }
    }

    public function analyse(Project $project, Report $report, string $path): void
    {
        fwrite(STDOUT, "==== Starting analyse() for project ID: {$project->getId()} with path: {$path}\n");
    
        $project->setState(ProjectState::ANALYSING);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        $this->logger->debug('Analysing project ' . $project->getId());
        fwrite(STDOUT, "==== Project state set to ANALYSING\n");
    
        // Initialisation de l'état comme étant SUCCESS (le moins grave)
        $state = AnalyseLevelState::SUCCESS;
        $this->logger->debug('Initial report state: ' . $state->name);
        fwrite(STDOUT, "==== Initial report state: {$state->name}\n");
    
        // On parcourt tous les plugins d'analyse
        foreach ($project->getAnalysePlugins() as $plugin) {
            $pluginType = $plugin->getType();
            fwrite(STDOUT, "==== Processing plugin: {$pluginType}\n");
    
            $analyseEntity = $plugin->getTypeEntity();
            $classMetadata = $this->entityManager->getClassMetadata(get_class($analyseEntity));
            $typeInfo = $this->manager->getRelatedObject($classMetadata->getName());
    
            /**
             * @var Analyse $service
             */
            $service = $this->serviceLocator->get($typeInfo->getServiceClass());
            $currentReport = $service->analyse($project, $analyseEntity, $path);
    
            $pluginState = $currentReport->getState();
            $this->logger->debug(sprintf('Plugin %s returned state: %s', $pluginType, $pluginState->name));
            fwrite(STDOUT, "==== Plugin {$pluginType} returned state: {$pluginState->name}\n");
    
            // Persistance du rapport du plugin
            $this->entityManager->persist($currentReport);
            $this->entityManager->flush();
           fwrite(STDOUT, "==== Report saved in DB with state: " );
    
            // Ajout au rapport global
            $report->{'set' . mb_ucfirst(u($pluginType)->camel())}($currentReport);
    
            fwrite(STDOUT, "==== Current report state: {$state->name}, checking plugin state: {$pluginState->name}\n");
    
            // Mise à jour si l'état du plugin est plus grave
            if ($pluginState->value < $state->value) {
                $state = $pluginState;
                fwrite(STDOUT, "==== Updated report state: {$state->name}\n");
            } else {
                fwrite(STDOUT, "==== Report state remains: {$state->name}\n");
            }
        }
    
        // Finalisation du rapport avec l'état déterminé
        $report->setState($state);
        $this->logger->debug('Final report state: ' . $report->getState()->name);
        fwrite(STDOUT, "==== Final report state: {$report->getState()->name}\n");
        fwrite(STDOUT, "==== Analysis completed for path: {$path}\n");
    }
    
    
}