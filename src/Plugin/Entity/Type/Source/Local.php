<?php

namespace App\Plugin\Entity\Type\Source;

use App\Plugin\Entity\Type\TypeAbstract;
use App\Plugin\Repository\Type\Source\Local as LocalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table(name: 'source_local')]
#[ORM\Entity(repositoryClass: LocalRepository::class)]
class Local extends TypeAbstract
{
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    protected ?string $path = null;

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
            !is_dir($path)
            || !$filesystem->exists($path)
            || !is_readable($path)
        ) {
            $context
                ->buildViolation('Directory not exists or is not readable.')
                ->atPath('path')
                ->addViolation();
        }
    }

    public function __toString()
    {
        return $this->path ?? '';
    }
}
