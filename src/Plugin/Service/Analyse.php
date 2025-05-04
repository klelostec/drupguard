<?php

namespace App\Plugin\Service;

use App\Entity\Plugin\Analyse as AnalyseEntity;
use App\Entity\Project;
use App\Form\Plugin\Analyse as AnalyseForm;
use App\Plugin\Annotation\PluginInfo;
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
    protected LoggerInterface $logger;

    public function __construct(
        TranslatorInterface $translator,
        LoggerInterface $logger,
    ) {
        parent::__construct($translator);
        $this->logger = $logger;
    }
    abstract public function analyse(Project $project, mixed $analyse, string $path): mixed;
}
