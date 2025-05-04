<?php

namespace App\Entity\Report;

use Doctrine\ORM\Mapping as ORM;

trait ReportLatestVersionItemTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $latestVersion = null;

    public function getLatestVersion(): ?string
    {
        return $this->latestVersion;
    }

    public function setLatestVersion(?string $latestVersion): static
    {
        $this->latestVersion = $latestVersion;

        return $this;
    }
}
