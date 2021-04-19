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
     * Project is missing security update(s).
     */
    const NOT_SECURE = 1;

    /**
     * Current release has been unpublished and is no longer available.
     */
    const REVOKED = 2;

    /**
     * Current release is no longer supported by the project maintainer.
     */
    const NOT_SUPPORTED = 3;

    /**
     * Project has a new release available, but it is not a security release.
     */
    const NOT_CURRENT = 4;

    /**
     * Project is up to date.
     */
    const CURRENT = 5;

    /**
     * Project's status cannot be checked.
     */
    const NOT_CHECKED = -1;

    /**
     * No available update data was found for project.
     */
    const UNKNOWN = -2;

    /**
     * There was a failure fetching available update data for this project.
     */
    const NOT_FETCHED = -3;

    /**
     * We need to (re)fetch available update data for this project.
     */
    const FETCH_PENDING = -4;

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
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $analyse;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $state;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $detail;

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

    public function getStateClass(): string
    {
        switch ($this->state) {
            case self::NOT_SECURE:
                return 'danger';
            case self::NOT_CURRENT:
            case self::REVOKED:
            case self::NOT_SUPPORTED:
                return 'warning';
            case self::CURRENT:
                return 'success';
            case self::NOT_CHECKED:
            case self::UNKNOWN:
            case self::NOT_FETCHED:
            case self::FETCH_PENDING:
            default:
                return 'secondary';
        }
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): self
    {
        $this->detail = $detail;

        return $this;
    }
}
