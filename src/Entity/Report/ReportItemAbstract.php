<?php

namespace App\Entity\Report;

use App\AnalyseLevelState;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

abstract class ReportItemAbstract implements ReportItemInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    protected ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $detail = null;

    #[ORM\Column(enumType: AnalyseLevelState::class)]
    protected ?AnalyseLevelState $state = null;

    #[ORM\Column(length: 255)]
    protected ?string $type = null;

    #[ORM\Column(length: 255)]
    protected ?string $currentVersion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getState(): ?AnalyseLevelState
    {
        return $this->state;
    }

    public function setState(AnalyseLevelState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCurrentVersion(): ?string
    {
        return $this->currentVersion;
    }

    public function setCurrentVersion(string $currentVersion): static
    {
        $this->currentVersion = $currentVersion;

        return $this;
    }
}
