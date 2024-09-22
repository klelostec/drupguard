<?php

namespace App\Entity;

use App\Repository\LocalSourceSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: LocalSourceSettingsRepository::class)]
class LocalSourceSettings extends SourceSettingsAbstract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context): void
    {
        $filesystem = new Filesystem();
        if (empty($this->getPath()) || !$filesystem->isAbsolutePath($this->getPath()) || !is_writable($this->getPath())) {
            $context
                ->buildViolation("Path is required, must be absolute and writable.")
                ->atPath('path')
                ->addViolation();
        }
    }

    public function __toString()
    {
        return $this->path;
    }
}
