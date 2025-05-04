<?php

namespace App\Plugin\Service\Type\Analyse;

use App\AnalyseLevelState;
use App\Entity\Plugin\Type\Analyse\ComposerAudit as ComposerAuditEntity;
use App\Entity\Project;
use App\Entity\Report\Type\ReportComposerAudit;
use App\Entity\Report\Type\ReportComposerAuditItem;
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

        $commandsDef = [
            'audit' => 'composer audit --no-scripts --no-plugins --no-cache --no-interaction --locked --format=json',
            'outdated' => 'composer outdated --ignore-platform-reqs --no-scripts --no-plugins --no-cache --no-interaction --locked --all --format=json',
        ];
        $commandsRes = [];
        foreach ($commandsDef as $commandType => $def) {
            $realCommand = explode(
                ' ',
                $def
            );

            $composerCmd = new Process($realCommand, $path);
            $composerCmd->setTimeout(60 * 60);
            try {
                $composerCmd->run();
                $output = $composerCmd->getOutput();
                $commandsRes[$commandType] = json_decode($output, true);
            } catch (\Exception $e) {
                $reportAnalyse->setState(AnalyseLevelState::FAILURE);
                $reportAnalyse->setDetail($this->translator->trans('Composer %type% failed. Detail: %detail%', ['type' => $commandType, 'detail' => $composerCmd->getErrorOutput()]));

                return $reportAnalyse;
            }
            unset($composerCmd);
        }
        $commandsRes['outdated'] = array_combine(array_column($commandsRes['outdated']['locked'], 'name'), $commandsRes['outdated']['locked']);

        $composerLock = json_decode($fileSystem->readFile($path.'/composer.lock'), true) ?? [];
        $state = AnalyseLevelState::SUCCESS;
        foreach ($composerLock['packages'] as $package) {
            $item = new ReportComposerAuditItem();
            $name = $package['name'];
            $item->setName($name);
            $item->setType($package['type'] ?? '');
            $item->setCurrentVersion($package['version']);
            $item->setLatestVersion($commandsRes['outdated'][$name]['latest']);

            $itemState = AnalyseLevelState::SUCCESS;
            $detail = [];
            if (isset($commandsRes['outdated'][$name]['latest-status'])) {
                switch ($commandsRes['outdated'][$name]['latest-status']) {
                    case 'update-possible':
                        $itemState = $itemState->value > AnalyseLevelState::WARNING->value ? AnalyseLevelState::WARNING : $itemState;
                        $detail[] = t('Major release available. Update possible.');
                        break;
                    case 'semver-safe-update':
                        $itemState = $itemState->value > AnalyseLevelState::WARNING->value ? AnalyseLevelState::WARNING : $itemState;
                        $detail[] = t('Patch or minor release available. Update recommended.');
                        break;
                    case 'up-to-date':
                    default:
                        break;
                }
            }
            if (isset($commandsRes['audit']['abandoned'][$name])) {
                $itemState = $itemState->value > (AnalyseLevelState::WARNING)->value ? AnalyseLevelState::WARNING : $itemState;
                $detail[] = t('Abandoned package.');
            }
            if (isset($commandsRes['audit']['advisories'][$name])) {
                $itemState = $itemState->value > AnalyseLevelState::SECURITY->value ? AnalyseLevelState::SECURITY : $itemState;
                $advisories = [];
                foreach ($commandsRes['audit']['advisories'][$name] as $advisory) {
                    $advisories[] = $advisory['title'].'<br><a href="'.$advisory['link'].'" target="_blank">'.$advisory['cve'].'</a>';
                }
                $detail[] = $this->translator->trans('Security advisories: %advisories%', ['advisories' => implode('<br><br>', $advisories)]);
            }

            $item->setState($itemState);
            if (!empty($detail)) {
                $strDetail = implode('<br><br>', $detail);
                $item->setDetail($strDetail);
            }
            $reportAnalyse->addItem($item);
            if ($itemState->value < $state->value) {
                $state = $itemState;
            }
        }

        $reportAnalyse->setState($state);

        return $reportAnalyse;
    }
}
