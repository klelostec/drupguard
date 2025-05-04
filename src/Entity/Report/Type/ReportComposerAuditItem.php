<?php

namespace App\Entity\Report\Type;

use App\Entity\Report\ReportItemAbstract;
use App\Entity\Report\ReportLatestVersionItemInterface;
use App\Entity\Report\ReportLatestVersionItemTrait;
use App\Repository\Report\ReportComposerAuditItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportComposerAuditItemRepository::class)]
class ReportComposerAuditItem extends ReportItemAbstract implements ReportLatestVersionItemInterface
{
    use ReportLatestVersionItemTrait;
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?ReportComposerAudit $reportComposerAudit = null;

    public function getReportComposerAudit(): ?ReportComposerAudit
    {
        return $this->reportComposerAudit;
    }

    public function setReportComposerAudit(?ReportComposerAudit $reportComposerAudit): static
    {
        $this->reportComposerAudit = $reportComposerAudit;

        return $this;
    }
}
