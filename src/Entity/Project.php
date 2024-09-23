<?php

namespace App\Entity;

use App\Plugin\Source\Entity\SourcePlugin;
use App\Repository\ProjectRepository;
use App\Security\ProjectRoles;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_MACHINENAME', fields: ['machine_name'])]
#[UniqueEntity(fields: ['machine_name'], message: 'There is already a project with this machine name')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\Regex(pattern: "/^[a-z0-9_]+$/i")]
    private ?string $machine_name = null;

    #[ORM\Column]
    private ?bool $isPublic = null;

    /**
     * @var Collection<int, ProjectMember>
     */
    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'project', cascade:["persist"], orphanRemoval:true)]
    #[Assert\Valid()]
    private Collection $projectMembers;

    /**
     * @var Collection<int, SourcePlugin>
     */
    #[ORM\OneToMany(targetEntity: SourcePlugin::class, mappedBy: 'project', cascade:["persist"], orphanRemoval:true)]
    #[Assert\Valid()]
    private Collection $sourcePlugins;

    public function __construct()
    {
        $this->projectMembers = new ArrayCollection();
        $this->sourcePlugins = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMachineName(): ?string
    {
        return $this->machine_name;
    }

    public function setMachineName(string $machineName): static
    {
        $this->machine_name = $machineName;

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * @return Collection<int, ProjectMember>
     */
    public function getProjectMembers(): Collection
    {
        return $this->projectMembers;
    }

    public function addProjectMember(ProjectMember $projectMember): static
    {
        if (!$this->projectMembers->contains($projectMember)) {
            $this->projectMembers->add($projectMember);
            $projectMember->setProject($this);
        }

        return $this;
    }

    public function removeProjectMember(ProjectMember $projectMember): static
    {
        if ($this->projectMembers->removeElement($projectMember)) {
            // set the owning side to null (unless already changed)
            if ($projectMember->getProject() === $this) {
                $projectMember->setProject(null);
            }
        }

        return $this;
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context): void
    {
        if (!$this->hasOwner()) {
            $context
                ->buildViolation("You must add at least one project member as owner.")
                ->atPath('projectMembers')
                ->addViolation();
        }
    }

    public function hasOwner(ProjectMember $excludedProjectMember = null): bool {
        if($this->getProjectMembers() === null) {
            return false;
        }

        $owner = false;
        foreach ($this->getProjectMembers() as $projectMember) {
            if (
                $projectMember->getRole() === ProjectRoles::OWNER &&
                (!$excludedProjectMember || ($projectMember->getId() !== $excludedProjectMember->getId()))
            ) {
                $owner = true;
                break;
            }
        }

        return $owner;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return Collection<int, SourcePlugin>
     */
    public function getSourcePlugins(): Collection
    {
        return $this->sourcePlugins;
    }

    public function addSourcePlugin(SourcePlugin $sourcePlugin): static
    {
        if (!$this->sourcePlugins->contains($sourcePlugin)) {
            $this->sourcePlugins->add($sourcePlugin);
            $sourcePlugin->setProject($this);
        }

        return $this;
    }

    public function removeSourcePlugin(SourcePlugin $sourcePlugin): static
    {
        if ($this->sourcePlugins->removeElement($sourcePlugin)) {
            // set the owning side to null (unless already changed)
            if ($sourcePlugin->getProject() === $this) {
                $sourcePlugin->setProject(null);
            }
        }

        return $this;
    }
}
