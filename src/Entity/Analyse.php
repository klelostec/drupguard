<?php

namespace App\Entity;

use App\Repository\AnalyseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AnalyseRepository::class)
 */
class Analyse
{
    const SUCCESS = 3;
    const WARNING = 2;
    const ERROR = 1;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isRunning;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $state;

    /**
     * @ORM\OneToMany(targetEntity=AnalyseItem::class, mappedBy="analyse", orphanRemoval=true)
     * @ORM\OrderBy({"type" = "ASC", "name" = "ASC"})
     */
    private $analyseItems;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="analyses")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $project;

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
                return 'secondary';
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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }
}
