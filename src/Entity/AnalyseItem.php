<?php

namespace App\Entity;

use App\Repository\AnalyseItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AnalyseItemRepository::class)
 */
class AnalyseItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $currentVersion;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $latestVersion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $recommandedVersion;

    /**
     * @ORM\ManyToOne(targetEntity=Analyse::class, inversedBy="analyseItems")
     * @ORM\JoinColumn(nullable=false)
     */
    private $analyse;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $state;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCurrentVersion(): ?string
    {
        return $this->currentVersion;
    }

    public function setCurrentVersion(string $currentVersion): self
    {
        $this->currentVersion = $currentVersion;

        return $this;
    }

    public function getLatestVersion(): ?string
    {
        return $this->latestVersion;
    }

    public function setLatestVersion(string $latestVersion): self
    {
        $this->latestVersion = $latestVersion;

        return $this;
    }

    public function getRecommandedVersion(): ?string
    {
        return $this->recommandedVersion;
    }

    public function setRecommandedVersion(?string $recommandedVersion): self
    {
        $this->recommandedVersion = $recommandedVersion;

        return $this;
    }

    public function getAnalyse(): ?Analyse
    {
        return $this->analyse;
    }

    public function setAnalyse(?Analyse $analyse): self
    {
        $this->analyse = $analyse;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }
}
