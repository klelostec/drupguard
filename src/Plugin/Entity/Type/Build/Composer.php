<?php

namespace App\Plugin\Entity\Type\Build;

use App\Plugin\Entity\Type\TypeAbstract;
use App\Plugin\Repository\Type\Build\Composer as ComposerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table(name: 'build_composer')]
#[ORM\Entity(repositoryClass: ComposerRepository::class)]
class Composer extends TypeAbstract
{
    #[ORM\Column(length: 255)]
    #[Assert\Choice(callback: 'getVersions')]
    #[Assert\NotBlank()]
    protected ?string $version = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $path = null;

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
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
        $version = $this->getVersion();
        if (empty($version)) {
            return;
        }
    }

    public static function getVersions(): array
    {
        return [
            'Version 2' => 'v2',
            'Version 1' => 'v1',
        ];
    }

    public function __toString()
    {
        return $this->versions ?? '';
    }
}
