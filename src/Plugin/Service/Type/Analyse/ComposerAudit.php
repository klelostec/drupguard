<?php

namespace App\Plugin\Service\Type\Analyse;

use App\AnalyseLevelState;
use App\Entity\Plugin\Type\Analyse\ComposerAudit as ComposerAuditEntity;
use App\Entity\Project;
use App\Entity\ReportComposerAudit;
use App\Entity\ReportComposerAuditItem;
use App\Form\Plugin\Type\Analyse\ComposerAudit as ComposerAuditForm;
use App\Plugin\Annotation\TypeInfo;
use App\Plugin\Service\Analyse;
use App\Repository\Plugin\Type\Analyse\ComposerAudit as ComposerAuditRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Symfony\Component\Translation\t;

#[TypeInfo(
    id: 'composer_audit',
    name: 'Composer audit',
    type: 'analyse',
    entityClass: ComposerAuditEntity::class,
    repositoryClass: ComposerAuditRepository::class,
    formClass: ComposerAuditForm::class,
    dependencies: [
        'source' => '*',
    ]
)]
class ComposerAudit extends Analyse
{
    public function analyse(Project $project, mixed $analyse, string $path): mixed
    {
        $fileSystem = new Filesystem();
        $reportAnalyse = new ReportComposerAudit();

        /**
         * @var ComposerAuditEntity $analyse
         */
        if (!empty($analyse->getPath())) {
            $path .= $analyse->getPath();
        }

        if (!$fileSystem->exists($path.'/composer.lock')) {
            $reportAnalyse->setState(AnalyseLevelState::FAILURE);
            $reportAnalyse->setDetail($this->translator->trans('Composer files not found.'));

            return $reportAnalyse;
        }

        $composerCmd = explode(
            ' ',
            'composer audit --no-scripts --no-plugins --no-cache --no-interaction --locked --format=json'
        );

        $composerAuditCmd = new Process($composerCmd, $path);
        $composerAuditCmd->setTimeout(60 * 60);
        $res = $composerAuditCmd->run();
        $composerAudit = [];
        if (0 !== $res) {
            try {
                $output = $composerAuditCmd->getOutput();
                $composerAudit = json_decode($output, true);
            } catch (\Exception $e) {
                $reportAnalyse->setState(AnalyseLevelState::FAILURE);
                $reportAnalyse->setDetail($this->translator->trans('Composer audit failed. Detail: %detail%', ['detail' => $composerAuditCmd->getErrorOutput()]));

                return $reportAnalyse;
            }
        }
        unset($composerAuditCmd);

        $composerLock = json_decode($fileSystem->readFile($path.'/composer.lock'), true) ?? [];
        $state = AnalyseLevelState::SUCCESS;
        foreach ($composerLock['packages'] as $package) {
            $item = new ReportComposerAuditItem();
            $name = $package['name'];
            $item->setName($name);
            $item->setType($package['type'] ?? '');
            $item->setCurrentVersion($package['version']);

            $itemState = AnalyseLevelState::SUCCESS;
            if (isset($composerAudit['abandoned'][$name])) {
                $itemState = AnalyseLevelState::WARNING;
                $item->setDetail(t('Abandoned package.'));
            } elseif (isset($composerAudit['advisories'][$name])) {
                $itemState = AnalyseLevelState::SECURITY;
                $advisories = [];
                foreach ($composerAudit['advisories'][$name] as $advisory) {
                    $advisories[] = $advisory['title'].'<br><a href="'.$advisory['link'].'" target="_blank">'.$advisory['cve'].'</a>';
                }
                $item->setDetail($this->translator->trans('Security advisories: %advisories%', ['advisories' => implode('<br><br>', $advisories)]));
            }
            $item->setState($itemState);
            $reportAnalyse->addItem($item);
            if ($itemState->value < $state->value) {
                $state = $itemState;
            }
        }

        $reportAnalyse->setState($state);

        return $reportAnalyse;
    }
}
