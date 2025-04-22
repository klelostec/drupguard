<?php

namespace App\Entity;

use App\AnalyseLevelState;
use App\Repository\ReportComposerAuditRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportComposerAuditRepository::class)]
class ReportComposerAudit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: AnalyseLevelState::class)]
    private ?AnalyseLevelState $state = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detail = null;

    /**
     * @var Collection<int, ReportComposerAuditItem>
     */
    #[ORM\OneToMany(targetEntity: ReportComposerAuditItem::class, mappedBy: 'reportComposerAudit', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): ?AnalyseLevelState
    {
        return $this->state;
    }

    public function setState(AnalyseLevelState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): static
    {
        $this->detail = $detail;

        return $this;
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
