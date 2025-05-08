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

    #[ORM\Column]
    protected ?bool $isDirectDependency;

    #[ORM\Column]
    protected ?bool $isDevPackage;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?ReportComposerAudit $reportComposerAudit = null;

    public function isDirectDependency(): ?bool
    {
        return $this->isDirectDependency;
    }

    public function setIsDirectDependency(?bool $isDirectDependency): static
    {
        $this->isDirectDependency = $isDirectDependency;

        return $this;
    }

    public function isDevPackage(): ?bool
    {
        return $this->isDevPackage;
    }

    public function setIsDevPackage(?bool $isDevPackage): static
    {
        $this->isDevPackage = $isDevPackage;

        return $this;
    }

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
