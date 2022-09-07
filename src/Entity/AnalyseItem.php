<?php

namespace App\Entity;

use App\Repository\AnalyseItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=AnalyseItemRepository::class)
 */
class AnalyseItem
{
    /**
     * Project is missing security update(s).
     */
    public const NOT_SECURE = 1;

    /**
     * Current release has been unpublished and is no longer available.
     */
    public const REVOKED = 2;

    /**
     * Current release is no longer supported by the project maintainer.
     */
    public const NOT_SUPPORTED = 3;

    /**
     * Project has a new release available, but it is not a security release.
     */
    public const NOT_CURRENT = 4;

    /**
     * Project is up to date.
     */
    public const CURRENT = 5;

    /**
     * Project's status cannot be checked.
     */
    public const NOT_CHECKED = -1;

    /**
     * No available update data was found for project.
     */
    public const UNKNOWN = -2;

    /**
     * There was a failure fetching available update data for this project.
     */
    public const NOT_FETCHED = -3;

    /**
     * We need to (re)fetch available update data for this project.
     */
    public const FETCH_PENDING = -4;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"show_project", "list_projects"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show_project", "list_projects"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show_project", "list_projects"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show_project", "list_projects"})
     */
    private $currentVersion;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show_project", "list_projects"})
     */
    private $latestVersion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"show_project", "list_projects"})
     */
    private $recommandedVersion;

    /**
     * @ORM\ManyToOne(targetEntity=Analyse::class, inversedBy="analyseItems")
     * @ORM\JoinColumn(nullable=false)
     */
    private $analyse;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show_project", "list_projects"})
     */
    private $state;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"show_project", "list_projects"})
     */
    private $detail;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show_project", "list_projects"})
     */
    private $machine_name;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"show_project", "list_projects"})
     */
    private $isIgnored;

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
            case self::NOT_SUPPORTED:
                return 'danger';
            case self::NOT_CURRENT:
            case self::REVOKED:
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

    public function getMachineName(): ?string
    {
        return $this->machine_name;
    }

    public function setMachineName(string $machine_name): self
    {
        $this->machine_name = $machine_name;

        return $this;
    }

    public function isIgnored(): ?bool
    {
        return $this->isIgnored;
    }

    public function setIsIgnored(bool $isIgnored): self
    {
        $this->isIgnored = $isIgnored;

        return $this;
    }
}
