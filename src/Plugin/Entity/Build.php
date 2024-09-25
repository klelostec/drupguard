<?php

namespace App\Plugin\Entity;

use App\Plugin\Entity\Type\Build\Composer;
use App\Plugin\Repository\Build as BuildRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BuildRepository::class)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(name: 'project', inversedBy: 'buildPlugins')
])]
class Build extends PluginAbstract
{
    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval:true)]
    //#[Assert\Valid()]
    protected ?Composer $composer = null;

    public function getComposer(): ?Composer
    {
        return $this->composer;
    }

    public function setComposer(?Composer $composer): static
    {
        $this->composer = $composer;

        return $this;
    }

}
