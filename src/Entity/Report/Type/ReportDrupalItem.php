<?php

namespace App\Entity\Report\Type;

use App\Entity\Report\ReportItemAbstract;
use App\Entity\Report\ReportLatestVersionItemInterface;
use App\Entity\Report\ReportLatestVersionItemTrait;
use App\Repository\Report\ReportDrupalItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportDrupalItemRepository::class)]
class ReportDrupalItem extends ReportItemAbstract implements ReportLatestVersionItemInterface
{
    use ReportLatestVersionItemTrait;

    #[ORM\Column(length: 255)]
    protected ?string $recommandedVersion = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?ReportDrupal $reportDrupal = null;

    public function getRecommandedVersion(): ?string
    {
        return $this->recommandedVersion;
    }

    public function setRecommandedVersion(string $recommandedVersion): static
    {
        $this->recommandedVersion = $recommandedVersion;

        return $this;
    }

    public function getReportDrupal(): ?ReportDrupal
    {
        return $this->reportDrupal;
    }

    public function setReportDrupal(?ReportDrupal $reportDrupal): static
    {
        $this->reportDrupal = $reportDrupal;

        return $this;
    }
}
