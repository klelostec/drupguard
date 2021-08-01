<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 * @UniqueEntity(fields="machineName", message="Machine name is already taken.")
 */
class Project
{
    public const EMAIL_LEVEL = [
        'Choose email level' => null,
        'Success' => Analyse::SUCCESS,
        'Warning' => Analyse::WARNING,
        'Error' => Analyse::ERROR,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\Regex(pattern="/^[a-z0-9_]+$/i", groups={"machine_name"})
     */
    private $machineName;

    /**
     * @ORM\Column(type="string", length=255)
     * @AppAssert\GitRemote()
     */
    private $gitRemoteRepository;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $gitBranch;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Regex(pattern="#^(/[\w-]+)*$#i")
     */
    private $drupalDirectory;

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
     * @Assert\Blank(groups={"not_cron"})
     * @AppAssert\CronExpression(groups={"cron"})
     */
    private $cronFrequency;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPublic;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="allowedProjects")
     * @ORM\OrderBy({"firstname" = "ASC", "lastname" = "ASC"})
     * @Assert\Blank(groups={"public"})
     */
    private $allowedUsers;

    /**
     * @ORM\OneToOne(targetEntity=Analyse::class)
     */
    private $lastAnalyse;

    /**
     * @ORM\OneToMany(targetEntity=Analyse::class, mappedBy="project", orphanRemoval=true)
     */
    private $analyses;

    /**
     * @ORM\Column(type="boolean")
     */
    private $needEmail;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     * @Assert\Blank(groups={"not_email"})
     * @Assert\Choice(callback="getEmailLevelChoices", groups={"email"})
     */
    private $emailLevel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Blank(groups={"not_email"})
     * @AppAssert\MultipleEmail(groups={"email"})
     */
    private $emailExtra;

    /**
     * @ORM\ManyToOne(targetEntity=AnalyseQueue::class, inversedBy="project")
     */
    private $analyseQueue;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $ignored_modules;

    /**
     * @var string[]
     */
    private $email_processed;

    /**
     * @var string[]
     */
    private $ignored_modules_processed;

    public function __construct()
    {
        $this->allowedUsers = new ArrayCollection();
        $this->analyses = new ArrayCollection();
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

    public function getMachineName(): ?string
    {
        return $this->machineName;
    }

    public function setMachineName(string $machineName): self
    {
        $this->machineName = $machineName;

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

    public function getGitBranch(): ?string
    {
        return $this->gitBranch;
    }

    public function setGitBranch(string $gitBranch): self
    {
        $this->gitBranch = $gitBranch;

        return $this;
    }

    public function getDrupalDirectory(): ?string
    {
        return $this->drupalDirectory;
    }

    public function setDrupalDirectory(?string $drupalDirectory): self
    {
        $this->drupalDirectory = $drupalDirectory;

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

    public function getLastAnalyse(): ?Analyse
    {
        return $this->lastAnalyse;
    }

    public function setLastAnalyse(?Analyse $lastAnalyse): self
    {
        $this->lastAnalyse = $lastAnalyse;

        return $this;
    }

    /**
     * @return Collection|Analyse[]
     */
    public function getAnalyses(): Collection
    {
        return $this->analyses;
    }

    public function addAnalyse(Analyse $analyse): self
    {
        if (!$this->analyses->contains($analyse)) {
            $this->analyses[] = $analyse;
            $analyse->setProject($this);
        }

        return $this;
    }

    public function removeAnalyse(Analyse $analyse): self
    {
        if ($this->analyses->removeElement($analyse)) {
            // set the owning side to null (unless already changed)
            if ($analyse->getProject() === $this) {
                $analyse->setProject(null);
            }
        }

        return $this;
    }

    public function needEmail(): ?bool
    {
        return $this->needEmail;
    }

    public function setNeedEmail(bool $needEmail): self
    {
        $this->needEmail = $needEmail;

        return $this;
    }

    public function getEmailLevel(): ?int
    {
        return $this->emailLevel;
    }

    public function setEmailLevel(?int $emailLevel): self
    {
        $this->emailLevel = $emailLevel;

        return $this;
    }

    public function getEmailLevelChoices()
    {
        $ret = array_slice(self::EMAIL_LEVEL, 1);
        return array_values($ret);
    }

    public function getEmailExtra(): ?string
    {
        return $this->emailExtra;
    }

    public function setEmailExtra(?string $emailExtra): self
    {
        $this->emailExtra = $emailExtra;

        return $this;
    }

    public function isPending(): ?bool
    {
        return !is_null($this->getAnalyseQueue());
    }

    public function getAnalyseQueue(): ?AnalyseQueue
    {
        return $this->analyseQueue;
    }

    public function setAnalyseQueue(?AnalyseQueue $analyseQueue): self
    {
        $this->analyseQueue = $analyseQueue;

        return $this;
    }

    public function getEmailsProcessed(): array
    {
        if (is_null($this->email_processed)) {
            $this->email_processed = [];
            if (!$this->getOwner()->isSuperAdmin()) {
                $this->email_processed[] = $this->getOwner()->getEmail();
            }
            foreach ($this->getAllowedUsers() as $user) {
                if ($user->isSuperAdmin() || !$user->isVerified()) {
                    continue;
                }
                $this->email_processed[] = $user->getEmail();
            }

            $extraEmails = $this->getEmailExtra();
            if (!empty($extraEmails)) {
                $extraEmails = str_replace("\r\n", "\n", $extraEmails);
                $extraEmails = explode("\n", $extraEmails);
                $this->email_processed = array_merge($this->email_processed, $extraEmails);
            }

            $this->email_processed = array_unique($this->email_processed);
        }

        return $this->email_processed;
    }

    public function getIgnoredModules(): ?string
    {
        return $this->ignored_modules;
    }

    public function setIgnoredModules(?string $ignored_modules): self
    {
        $this->ignored_modules = $ignored_modules;

        return $this;
    }

    public function getIgnoredModulesProcessed(): array
    {
        if (!isset($this->ignored_modules_processed)) {
            $this->ignored_modules_processed = [];
            $modules = $this->getIgnoredModules();
            if (!empty($modules)) {
                $modules = str_replace("\r\n", "\n", $modules);
                $modules = explode("\n", $modules);
                $this->ignored_modules_processed = array_unique($modules);
            }
        }

        return $this->ignored_modules_processed;
    }
}
