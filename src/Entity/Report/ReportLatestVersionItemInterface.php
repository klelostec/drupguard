<?php

namespace App\Entity\Report;

interface ReportLatestVersionItemInterface
{
    public function getLatestVersion(): ?string;

    public function setLatestVersion(?string $latestVersion): static;
}
