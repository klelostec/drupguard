<?php

namespace App\Entity\Report\Type;

use App\Entity\Report;
use App\Entity\Report\ReportAbstract;
use App\Repository\Report\ReportComposerAuditRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportComposerAuditRepository::class)]
class ReportComposerAudit extends ReportAbstract
{
    #[ORM\ManyToOne(inversedBy: 'composerAudit')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Report $report = null;

    /**
     * @var Collection<int, ReportComposerAuditItem>
     */
    #[ORM\OneToMany(targetEntity: ReportComposerAuditItem::class, mappedBy: 'reportComposerAudit', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @return Collection<int, ReportComposerAuditItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ReportComposerAuditItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setReportComposerAudit($this);
        }

        return $this;
    }

    public function removeItem(ReportComposerAuditItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getReportComposerAudit() === $this) {
                $item->setReportComposerAudit(null);
            }
        }

        return $this;
    }
}
