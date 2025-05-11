<?php

namespace App\Plugin\Service;

use App\Entity\Plugin\Source as SourceEntity;
use App\Entity\Plugin\Type\PathTypeInterface;
use App\Entity\Project;
use App\Form\Plugin\Source as SourceForm;
use App\Plugin\Annotation\PluginInfo;
use App\Repository\Plugin\Source as SourceRepository;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[PluginInfo(
    id: 'source',
    name: 'Source',
    entityClass: SourceEntity::class,
    repositoryClass: SourceRepository::class,
    formClass: SourceForm::class
)]
abstract class Source extends Plugin
{

    abstract public function source(Project $project, mixed $source): string;

    protected function getPath(Project $project, mixed $source): string
    {
        if ($source instanceof PathTypeInterface && !empty($source->getPath())) {
            return $source->getPath();
        }

        return $this->appKernel->getProjectDir().'/workspace/'.$project->getMachineName();
    }
}
