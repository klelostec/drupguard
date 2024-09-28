<?php

namespace App\Entity\Plugin\Type;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class PathTypeAbstract extends TypeAbstract
{
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $path = null;

    protected bool $checkPathOnFileSystem = false;

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function validate(ExecutionContextInterface $context): void
    {
        $path = $this->getPath();
        if (empty($path)) {
            return;
        }

        $filesystem = new Filesystem();
        if (
            !$filesystem->isAbsolutePath($path)
            || \DIRECTORY_SEPARATOR === mb_substr($path, -1)
        ) {
            $context
                ->buildViolation('Path is not valid, it must be absolute and must not end with a "'.\DIRECTORY_SEPARATOR.'".')
                ->atPath('path')
                ->addViolation();
        } elseif (
            $this->checkPathOnFileSystem && (
                !is_dir($path)
                || !$filesystem->exists($path)
                || !is_readable($path)
            )
        ) {
            $context
                ->buildViolation('Directory not exists or is not readable.')
                ->atPath('path')
                ->addViolation();
        }
    }

    public function __toString()
    {
        return !empty($this->getPath()) ? ' - '.$this->getPath() : '';
    }
}
