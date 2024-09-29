<?php

namespace App\Entity\Plugin;

use App\Entity\Plugin\Type\TypeInterface;
use App\Entity\Project;
use Doctrine\ORM\Mapping as ORM;

use function Symfony\Component\String\u;

abstract class PluginAbstract implements PluginInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    protected ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'abstractPlugins')]
    protected ?Project $project = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

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

    public function getTypeEntity(): ?TypeInterface
    {
        if (!empty($this->type)) {
            $methodCandidate = 'get'.mb_ucfirst(u($this->type)->camel());
            if (method_exists($this, $methodCandidate)) {
                return $this->{$methodCandidate}();
            }
        }

        return null;
    }

    public function __toString()
    {
        return $this->getTypeEntity() ?? '';
    }
}
