<?php

namespace App\Entity;

use App\Repository\SourcePluginRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SourcePluginRepository::class)]
class SourcePlugin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval:true)]
    #[Assert\Valid()]
    private ?LocalSourceSettings $localSourceSettings = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval:true)]
    #[Assert\Valid()]
    private ?GitSourceSettings $gitSourceSettings = null;

    #[ORM\ManyToOne(inversedBy: 'sourcePlugins')]
    private ?Project $project = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLocalSourceSettings(): ?LocalSourceSettings
    {
        return $this->localSourceSettings;
    }

    public function setLocalSourceSettings(?LocalSourceSettings $localSourceSettings): static
    {
        $this->localSourceSettings = $localSourceSettings;

        return $this;
    }

    public function getGitSourceSettings(): ?GitSourceSettings
    {
        return $this->gitSourceSettings;
    }

    public function setGitSourceSettings(?GitSourceSettings $gitSourceSettings): static
    {
        $this->gitSourceSettings = $gitSourceSettings;

        return $this;
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

    public function getSourceSettings(): ?SourceSettingsInterface {
        if (empty($this->type)) {
            return null;
        }
        $methodCandidate = 'get' . ucfirst($this->type) . 'SourceSettings';
        if (method_exists($this, $methodCandidate)) {
            return $this->{$methodCandidate}();
        }

        return null;
    }

    public function __toString()
    {
        $label = $this->type;
        $sourceSettings = $this->getSourceSettings();
        if ($sourceSettings) {
            $label .= ' - ' . $sourceSettings;
        }

        return $label;
    }
}
