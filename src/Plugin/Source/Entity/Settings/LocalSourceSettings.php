<?php

namespace App\Plugin\Source\Entity\Settings;

use App\Plugin\Source\Repository\LocalSourceSettingsRepository;
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

    public function setPath(?string $path): static
    {
        $this->path = $path;

        return $this;
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context): void
    {
        $filesystem = new Filesystem();
        $path = $this->getPath();
        if (
            empty($path) ||
            !$filesystem->isAbsolutePath($path) ||
            mb_substr($path, -1) === \DIRECTORY_SEPARATOR
        ) {
            $context
                ->buildViolation('Path is not valid, it must be absolute and must not end with a "' . \DIRECTORY_SEPARATOR . '".')
                ->atPath('path')
                ->addViolation();
        }
        elseif (
            !is_dir($path) ||
            !$filesystem->exists($path) ||
            !is_writable($path)
        ) {
            $context
                ->buildViolation("Directory not exists or is not writable.")
                ->atPath('path')
                ->addViolation();
        }
    }

    public function __toString()
    {
        return $this->path ?? '';
    }
}
