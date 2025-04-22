<?php

namespace App\Plugin\Service;

use App\Entity\Plugin\Analyse as AnalyseEntity;
use App\Entity\Project;
use App\Form\Plugin\Analyse as AnalyseForm;
use App\Plugin\Annotation\PluginInfo;
use App\Repository\Plugin\Analyse as AnalyseRepository;

#[PluginInfo(
    id: 'analyse',
    name: 'Analyse',
    entityClass: AnalyseEntity::class,
    repositoryClass: AnalyseRepository::class,
    formClass: AnalyseForm::class
)]
abstract class Analyse extends Plugin
{
    abstract public function analyse(Project $project, mixed $analyse, string $path): mixed;
}
