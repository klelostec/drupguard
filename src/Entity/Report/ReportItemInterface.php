<?php

namespace App\Entity\Report;

use App\AnalyseLevelState;

interface ReportItemInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(string $name): static;

    public function getDetail(): ?string;

    public function setDetail(?string $detail): static;

    public function getState(): ?AnalyseLevelState;

    public function setState(AnalyseLevelState $state): static;

    public function getType(): ?string;

    public function setType(string $type): static;

    public function getCurrentVersion(): ?string;

    public function setCurrentVersion(string $currentVersion): static;
}
