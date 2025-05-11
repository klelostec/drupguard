<?php

namespace App\Plugin\Service;

use App\Entity\Plugin\Analyse as AnalyseEntity;
use App\Entity\Plugin\Type\PathTypeInterface;
use App\Entity\Project;
use App\Entity\Report\ReportInterface;
use App\Form\Plugin\Analyse as AnalyseForm;
use App\Plugin\Annotation\PluginInfo;
use App\Plugin\Annotation\TypeInfo;
use App\Repository\Plugin\Analyse as AnalyseRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[PluginInfo(
    id: 'analyse',
    name: 'Analyse',
    entityClass: AnalyseEntity::class,
    repositoryClass: AnalyseRepository::class,
    formClass: AnalyseForm::class
)]
abstract class Analyse extends Plugin
{
    public function analyse(Project $project, mixed $analyse, string $path, TypeInfo $typeInfo): ReportInterface {
        if ($analyse instanceof PathTypeInterface && !empty($analyse->getPath())) {
            $path .= $analyse->getPath();
        }

        $currentReport = $this->processAnalyse($project, $analyse, $path, $typeInfo);

        $currentReport->setName($analyse->getName() ?? $typeInfo->getName());
        $currentReport->setPath($path);

        return $currentReport;
    }

    abstract public function processAnalyse(Project $project, mixed $analyse, string $path, TypeInfo $typeInfo): ReportInterface;
}
