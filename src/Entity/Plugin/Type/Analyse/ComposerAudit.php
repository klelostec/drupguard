<?php

namespace App\Entity\Plugin\Type\Analyse;

use App\Entity\Plugin\Type\NameTypeInterface;
use App\Entity\Plugin\Type\NameTypeTrait;
use App\Entity\Plugin\Type\PathTypeInterface;
use App\Entity\Plugin\Type\PathTypeTrait;
use App\Entity\Plugin\Type\TypeAbstract;
use App\Repository\Plugin\Type\Analyse\ComposerAudit as ComposerAuditRepository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'analyse_composer_audit')]
#[ORM\Entity(repositoryClass: ComposerAuditRepository::class)]
#[AppAssert\Plugin\Path(checkPathFileSystem: false)]
class ComposerAudit extends TypeAbstract implements PathTypeInterface, NameTypeInterface
{
    use NameTypeTrait;
    use PathTypeTrait {
        PathTypeTrait::__toString as traitToString;
    }

    #[ORM\Column]
    private bool $withDevPackages = true;

    #[ORM\Column]
    private bool $withDependencies = true;

    public function withDevPackages(): bool
    {
        return $this->withDevPackages;
    }

    public function setWithDevPackages(bool $withDevPackages): static
    {
        $this->withDevPackages = $withDevPackages;

        return $this;
    }

    public function withDependencies(): bool
    {
        return $this->withDependencies;
    }

    public function setWithDependencies(bool $withDependencies): static
    {
        $this->withDependencies = $withDependencies;

        return $this;
    }

    public function __toString()
    {
        return 'Composer audit'.$this->traitToString();
    }
}
