<?php

namespace App\Plugin\Entity;

use App\Entity\Project;
use App\Plugin\Entity\Type\TypeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class PluginAbstract implements PluginInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
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

    public function setType(string $type): static
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
            $methodCandidate = 'get'.ucfirst($this->type);
            if (method_exists($this, $methodCandidate)) {
                return $this->{$methodCandidate}();
            }
        }

        return null;
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context): void
    {
        if (empty($this->getType())) {
            $context
                ->buildViolation('Type is required.')
                ->atPath('type')
                ->addViolation();

            return;
        }

        $typePlugin = $this->getTypeEntity();
        if (empty($typePlugin)) {
            $context
                ->buildViolation(ucfirst($this->getType()).' is required.')
                ->atPath($this->getType())
                ->addViolation();
        } else {
            $context
                ->getValidator()
                ->inContext($context)
                ->atPath($this->getType())
                ->validate($typePlugin, new Assert\Valid())
            ;
        }
    }

    public function __toString()
    {
        $label = $this->getType();
        $typePlugin = $this->getTypeEntity();
        if ($typePlugin) {
            $label .= ' - '.$typePlugin;
        }

        return $label;
    }
}
