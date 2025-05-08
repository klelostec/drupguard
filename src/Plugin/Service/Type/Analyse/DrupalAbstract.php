<?php

namespace App\Plugin\Service\Type\Analyse;

use App\AnalyseLevelState;
use App\Entity\Project;
use App\Entity\Report\ReportInterface;
use App\Entity\Report\Type\ReportDrupal;
use App\Plugin\Annotation\TypeInfo;
use App\Plugin\Service\Analyse;

abstract class DrupalAbstract extends Analyse
{
    public function processAnalyse(Project $project, mixed $analyse, string $path, TypeInfo $typeInfo): ReportInterface
    {
        $reportAnalyse = new ReportDrupal();
        $reportAnalyse->setState(AnalyseLevelState::SUCCESS);
        $reportAnalyse->setDetail($this->translator->trans('Drupal analyse is not implemented yet.'));

        return $reportAnalyse;
    }
}
