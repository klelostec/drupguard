<?php

namespace App\Entity;

use App\Repository\AnalyseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=AnalyseRepository::class)
 */
class Analyse
{
    public const SUCCESS = 3;
    public const WARNING = 2;
    public const ERROR = 1;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"show_project", "list_projects"})
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"show_project", "list_projects"})
     */
    private $date;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"show_project", "list_projects"})
     */
    private $isRunning;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"show_project", "list_projects"})
     */
    private $state;

    /**
     * @ORM\OneToMany(targetEntity=AnalyseItem::class, mappedBy="analyse", orphanRemoval=true)
     * @ORM\OrderBy({"type" = "ASC", "name" = "ASC"})
     * @Groups({"show_project", "list_projects"})
     */
    private $analyseItems;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="analyses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"show_project", "list_projects"})
     */
    private $message;

    public function __construct()
    {
        $this->analyseItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function isRunning(): ?bool
    {
        return $this->isRunning;
    }

    public function setIsRunning(bool $isRunning): self
    {
        $this->isRunning = $isRunning;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getStateClass(): string
    {
        switch ($this->state) {
            case self::ERROR:
                return 'danger';
            case self::WARNING:
                return 'warning';
            case self::SUCCESS:
                return 'success';
            default:
                return 'other';
        }
    }

    public function getStateLabel(): string
    {
        switch ($this->state) {
            case self::ERROR:
                return 'Error';
            case self::WARNING:
                return 'Warning';
            case self::SUCCESS:
                return 'Success';
            default:
                return 'Other';
        }
    }

    /**
     * @return Collection|AnalyseItem[]
     */
    public function getAnalyseItems(): Collection
    {
        return $this->analyseItems;
    }

    public function addAnalyseItem(AnalyseItem $analyseItem): self
    {
        if (!$this->analyseItems->contains($analyseItem)) {
            $this->analyseItems[] = $analyseItem;
            $analyseItem->setAnalyse($this);
        }

        return $this;
    }

    public function removeAnalyseItem(AnalyseItem $analyseItem): self
    {
        if ($this->analyseItems->removeElement($analyseItem)) {
            // set the owning side to null (unless already changed)
            if ($analyseItem->getAnalyse() === $this) {
                $analyseItem->setAnalyse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AnalyseItem[]
     */
    public function getActiveAnalyseItems(): Collection
    {
        return $this->getAnalyseItems()->filter(function (AnalyseItem $analyseItem) {
            return !$analyseItem->isIgnored();
        });
    }

    /**
     * @return Collection|AnalyseItem[]
     */
    public function getIgnoredAnalyseItems(): Collection
    {
        return $this->getAnalyseItems()->filter(function (AnalyseItem $analyseItem) {
            return $analyseItem->isIgnored();
        });
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
