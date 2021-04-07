<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 */
class Project
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
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $gitRemoteRepository;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $owner;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasCron;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cronFrequency;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPublic;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="allowedProjects")
     */
    private $allowedUsers;

    public function __construct()
    {
        $this->allowedUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getGitRemoteRepository(): ?string
    {
        return $this->gitRemoteRepository;
    }

    public function setGitRemoteRepository(string $gitRemoteRepository): self
    {
        $this->gitRemoteRepository = $gitRemoteRepository;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function hasCron(): ?bool
    {
        return $this->hasCron;
    }

    public function setHasCron(bool $hasCron): self
    {
        $this->hasCron = $hasCron;

        return $this;
    }

    public function getCronFrequency(): ?string
    {
        return $this->cronFrequency;
    }

    public function setCronFrequency(?string $cronFrequency): self
    {
        $this->cronFrequency = $cronFrequency;

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getAllowedUsers(): Collection
    {
        return $this->allowedUsers;
    }

    public function addAllowedUser(User $allowedUser): self
    {
        if (!$this->allowedUsers->contains($allowedUser)) {
            $this->allowedUsers[] = $allowedUser;
        }

        return $this;
    }

    public function removeAllowedUser(User $allowedUser): self
    {
        $this->allowedUsers->removeElement($allowedUser);

        return $this;
    }
}
