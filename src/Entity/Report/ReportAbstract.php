<?php

namespace App\Entity\Report;

use App\AnalyseLevelState;
use App\Entity\Report;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use function Symfony\Component\String\u;

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

    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $path = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): static
    {
        $this->path = $path;

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

    public function getTemplateName(): string {
        return u(get_class($this))->replace('App\Entity\Report\Type\Report', '')->snake();
    }

    abstract public function getItems(): Collection;
}
