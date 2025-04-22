<?php

namespace App\Entity\Report;

use App\AnalyseLevelState;
use App\Entity\Report;

interface ReportInterface
{
    public function getId(): ?int;

    public function getState(): ?AnalyseLevelState;

    public function getDetail(): ?string;

    public function getWeight(): int;

    public function getReport(): ?Report;

    public function setState(AnalyseLevelState $state): static;

    public function setDetail(?string $detail): static;

    public function setWeight(int $weight): static;

    public function setReport(?Report $report): static;
}
