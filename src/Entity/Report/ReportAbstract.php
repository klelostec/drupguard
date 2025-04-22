<?php

namespace App\Entity\Report;

use App\AnalyseLevelState;
use App\Entity\Report;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

abstract class ReportAbstract implements ReportInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(enumType: AnalyseLevelState::class)]
    protected ?AnalyseLevelState $state = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $detail = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected int $weight = 0;

    #[ORM\ManyToOne()]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Report $report = null;

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

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report): static
    {
        $this->report = $report;

        return $this;
    }
}
