<?php

namespace App\Entity\Report\Type;

use App\Entity\Report;
use App\Entity\Report\ReportAbstract;
use App\Repository\Report\ReportDrupalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportDrupalRepository::class)]
class ReportDrupal extends ReportAbstract
{
    #[ORM\ManyToOne(inversedBy: 'drupal')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Report $report = null;

    /**
     * @var Collection<int, ReportDrupalItem>
     */
    #[ORM\OneToMany(targetEntity: ReportDrupalItem::class, mappedBy: 'reportDrupal', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @return Collection<int, ReportDrupalItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ReportDrupalItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setReportDrupal($this);
        }

        return $this;
    }

    public function removeItem(ReportDrupalItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getReportDrupal() === $this) {
                $item->setReportDrupal(null);
            }
        }

        return $this;
    }
}
