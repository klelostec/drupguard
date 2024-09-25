<?php

namespace App\Entity;

use App\Repository\ProjectMemberRepository;
use App\Security\ProjectRoles;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ProjectMemberRepository::class)]
class ProjectMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'projectMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(inversedBy: 'projectMembers')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'projectMembers')]
    private ?Group $groups = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getGroups(): ?Group
    {
        return $this->groups;
    }

    public function setGroups(?Group $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context): void
    {
        if (null === $this->getGroups() && null === $this->getUser()) {
            $context
                ->buildViolation('You must select a user or a group.')
                ->atPath('groups')
                ->addViolation();
            $context
                ->buildViolation('You must select a user or a group.')
                ->atPath('user')
                ->addViolation();
        } elseif (null !== $this->getGroups() && null !== $this->getUser()) {
            $context
                ->buildViolation('You must select a user or a group.')
                ->atPath('groups')
                ->addViolation();
            $context
                ->buildViolation('You must select a user or a group.')
                ->atPath('user')
                ->addViolation();
        }
    }

    public function __toString()
    {
        $str = [];
        if (null !== $this->getUser()) {
            $str[] = 'User '.$this->getUser()->getUsername();
        }
        if (null !== $this->getGroups()) {
            $str[] = 'Group '.$this->getGroups()->getName();
        }

        $str[] = array_flip(ProjectRoles::getRoles())[$this->getRole()] ?? 'Unknown';

        return implode(' - ', $str);
    }
}
