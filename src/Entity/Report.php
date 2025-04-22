<?php

namespace App\Entity;

use App\AnalyseLevelState;
use App\Entity\Report\Type\ReportComposerAudit;
use App\Entity\Report\Type\ReportDrupal;
use App\Repository\ReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datetime = null;

    #[ORM\Column(nullable: true, enumType: AnalyseLevelState::class)]
    private ?AnalyseLevelState $state = null;

    /**
     * @var Collection<int, ReportComposerAudit>
     */
    #[ORM\OneToMany(targetEntity: ReportComposerAudit::class, mappedBy: 'report', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $composerAudit;

    /**
     * @var Collection<int, ReportDrupal>
     */
    #[ORM\OneToMany(targetEntity: ReportDrupal::class, mappedBy: 'report', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $drupal;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detail = null;

    public function __construct()
    {
        $this->composerAudit = new ArrayCollection();
        $this->drupal = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): static
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getState(): ?AnalyseLevelState
    {
        return $this->state;
    }

    public function setState(?AnalyseLevelState $state): static
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Collection<int, \App\Entity\Report\Type\ReportComposerAudit>
     */
    public function getComposerAudit(): Collection
    {
        return $this->composerAudit;
    }

    public function addComposerAudit(ReportComposerAudit $composerAudit): static
    {
        if (!$this->composerAudit->contains($composerAudit)) {
            $this->composerAudit->add($composerAudit);
            $composerAudit->setReport($this);
        }

        return $this;
    }

    public function removeComposerAudit(ReportComposerAudit $composerAudit): static
    {
        if ($this->composerAudit->removeElement($composerAudit)) {
            // set the owning side to null (unless already changed)
            if ($composerAudit->getReport() === $this) {
                $composerAudit->setReport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, \App\Entity\Report\Type\ReportDrupal>
     */
    public function getDrupal(): Collection
    {
        return $this->drupal;
    }

    public function addDrupal(ReportDrupal $drupal): static
    {
        if (!$this->drupal->contains($drupal)) {
            $this->drupal->add($drupal);
            $drupal->setReport($this);
        }

        return $this;
    }

    public function removeDrupal(ReportDrupal $drupal): static
    {
        if ($this->drupal->removeElement($drupal)) {
            // set the owning side to null (unless already changed)
            if ($drupal->getReport() === $this) {
                $drupal->setReport(null);
            }
        }

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

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
}
