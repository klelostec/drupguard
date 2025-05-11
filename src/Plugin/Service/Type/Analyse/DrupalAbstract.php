<?php

namespace App\Plugin\Service\Type\Analyse;

use App\AnalyseLevelState;
use App\Entity\Project;
use App\Entity\Report\ReportInterface;
use App\Entity\Report\Type\ReportComposerAuditItem;
use App\Entity\Report\Type\ReportDrupal;
use App\Entity\Report\Type\ReportDrupalItem;
use App\Plugin\Annotation\TypeInfo;
use App\Plugin\Service\Analyse;
use App\Plugin\Service\Type\Analyse\Drupal\DrupalFinder;
use App\Plugin\Service\Type\Analyse\Drupal\UpdateCompare;
use App\Plugin\Service\Type\Analyse\Drupal\UpdateFetcherInterface;
use App\Plugin\Service\Type\Analyse\Drupal\UpdateManagerInterface;
use App\Plugin\Service\Type\Analyse\Drupal\UpdateProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class DrupalAbstract extends Analyse
{
    protected DrupalFinder $drupalFinder;
    protected UpdateCompare $updateCompare;
    protected UpdateProcessor $updateProcessor;

    public function __construct(
        TranslatorInterface $translator,
        LoggerInterface $logger,
        KernelInterface $appKernel,
        DrupalFinder $drupalFinder,
        UpdateCompare $updateCompare,
        UpdateProcessor $updateProcessor,
    ) {
        parent::__construct($translator, $logger, $appKernel);
        $this->drupalFinder = $drupalFinder;
        $this->updateCompare = $updateCompare;
        $this->updateProcessor = $updateProcessor;
    }

    public function processAnalyse(Project $project, mixed $analyse, string $path, TypeInfo $typeInfo): ReportInterface
    {
        $reportAnalyse = new ReportDrupal();

        $this->drupalFinder->find($path, $typeInfo->getId());
        $this->updateProcessor->setCompat($this->drupalFinder->getCompat());
        $items = $this->drupalFinder->getItems();
        foreach ($items as $itemType => $subItems) {
            $this->updateCompare->update_process_project_info($subItems);
            $state = AnalyseLevelState::SUCCESS;
            foreach ($subItems as $name => $currentSubItem) {
                $available = $this->updateProcessor->processFetchTask($currentSubItem);
                $this->updateCompare->update_calculate_project_update_status(
                    $currentSubItem,
                    $available
                );
                $this->logger->debug(print_r($currentSubItem, true));
                $item = new ReportDrupalItem();
                $item
                    ->setName($currentSubItem['info']['name'])
                    ->setType($currentSubItem['info']['type'] ?? '')
                    ->setCurrentVersion($currentSubItem['existing_version'])
                    ->setLatestVersion($currentSubItem['latest_version'] ?? '')
                    ->setRecommandedVersion($currentSubItem['recommended'] ?? '');

                switch ($currentSubItem['status']) {
                    case UpdateManagerInterface::NOT_SECURE:
                        $itemState = AnalyseLevelState::SECURITY;
                        break;
                    case UpdateManagerInterface::REVOKED:
                        $itemState = AnalyseLevelState::DANGER;
                        break;
                    case UpdateManagerInterface::NOT_SUPPORTED:
                    case UpdateFetcherInterface::NOT_FETCHED:
                    case UpdateFetcherInterface::UNKNOWN:
                    case UpdateFetcherInterface::FETCH_PENDING:
                    case UpdateFetcherInterface::NOT_CHECKED:
                    case UpdateManagerInterface::NOT_CURRENT:
                        $itemState = AnalyseLevelState::WARNING;
                        break;
                    case UpdateManagerInterface::CURRENT:
                    default:
                        $itemState = AnalyseLevelState::SUCCESS;
                        break;
                }
                $item->setState($itemState);

                $detail = '';
                if (!empty($currentSubItem['also'])) {
                    $detail .= '<div>Major version available : <br><ul>';
                    foreach ($currentSubItem['also'] as $also) {
                        $detail .= '<li><a href="' . $currentSubItem['releases'][$also]['release_link'] . '" target="_blank">' . $currentSubItem['releases'][$also]['version'] . '</a></li>';
                    }
                    $detail .= '</ul></div>';
                }
                if (!empty($currentSubItem['security updates'])) {
                    $detail .= '<div>Security update available : <br><ul>';
                    foreach ($currentSubItem['security updates'] as $securityUpdate) {
                        $detail .= '<li><a href="' . $securityUpdate['release_link'] . '" target="_blank">' . $securityUpdate['version'] . '</a></li>';
                    }
                    $detail .= '</ul></div>';
                }

                if (!empty($currentSubItem['reason'])) {
                    $detail .= '<div>' . $currentSubItem['reason'] . '</div>';
                }
                if (!empty($currentSubItem['extra'])) {
                    foreach ($currentSubItem['extra'] as $extra) {
                        $detail .= '<div><strong>' . $extra['label'] . '</strong><br>' . $extra['data'] . '</div>';
                    }
                }
                $item->setDetail($detail);

                if ($itemState->value < $state->value) {
                    $state = $itemState;
                }

                $reportAnalyse->addItem($item);
            }
        }
        $reportAnalyse->setState($state);

        return $reportAnalyse;
    }
}
