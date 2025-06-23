<?php

namespace App\Plugin\Service;

use App\Entity\Plugin\Build as BuildEntity;
use App\Entity\Project;
use App\Form\Plugin\Build as BuildForm;
use App\Plugin\Annotation\PluginInfo;
use App\Repository\Plugin\Build as BuildRepository;

#[PluginInfo(
    id: 'build',
    name: 'Build',
    entityClass: BuildEntity::class,
    repositoryClass: BuildRepository::class,
    formClass: BuildForm::class,
    weight: 1
)]
abstract class Build extends Plugin
{
    abstract public function build(Project $project, mixed $build, string $path);
}
