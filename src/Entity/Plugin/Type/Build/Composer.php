<?php

namespace App\Entity\Plugin\Type\Build;

use App\Entity\Plugin\Type\PathTypeInterface;
use App\Entity\Plugin\Type\PathTypeTrait;
use App\Entity\Plugin\Type\TypeAbstract;
use App\Repository\Plugin\Type\Build\Composer as ComposerRepository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'build_composer')]
#[ORM\Entity(repositoryClass: ComposerRepository::class)]
#[AppAssert\Plugin\Path(checkPathFileSystem: false)]
class Composer extends TypeAbstract implements PathTypeInterface
{
    use PathTypeTrait {
        PathTypeTrait::__toString as traitToString;
    }

    #[ORM\Column(length: 255)]
    #[Assert\Choice(callback: 'getVersions')]
    #[Assert\NotBlank()]
    protected ?string $version = null;

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
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
        $version = $this->getVersion() ? ' - '.array_flip(static::getVersions())[$this->version] : '';

        return 'Composer'.$version.$this->traitToString();
    }
}
