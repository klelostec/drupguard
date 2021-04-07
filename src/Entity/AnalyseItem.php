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
    private $projectVersion;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastAvailableVersion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastAvailableSecurityVersion;

    /**
     * @ORM\ManyToOne(targetEntity=Analyse::class, inversedBy="analyseItems")
     * @ORM\JoinColumn(nullable=false)
     */
    private $analyse;

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

    public function getProjectVersion(): ?string
    {
        return $this->projectVersion;
    }

    public function setProjectVersion(string $projectVersion): self
    {
        $this->projectVersion = $projectVersion;

        return $this;
    }

    public function getLastAvailableVersion(): ?string
    {
        return $this->lastAvailableVersion;
    }

    public function setLastAvailableVersion(string $lastAvailableVersion): self
    {
        $this->lastAvailableVersion = $lastAvailableVersion;

        return $this;
    }

    public function getLastAvailableSecurityVersion(): ?string
    {
        return $this->lastAvailableSecurityVersion;
    }

    public function setLastAvailableSecurityVersion(?string $lastAvailableSecurityVersion): self
    {
        $this->lastAvailableSecurityVersion = $lastAvailableSecurityVersion;

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
}
