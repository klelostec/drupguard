<?php

namespace App\Entity\Plugin;

use App\Entity\Plugin\Type\Build\Composer;
use App\Form\Plugin\Build as BuildForm;
use App\Plugin\PluginInfo;
use App\Repository\Plugin\Build as BuildRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BuildRepository::class)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(name: 'project', inversedBy: 'buildPlugins'),
])]
#[PluginInfo(id: 'build', name: 'Build', entityClass: Build::class, repositoryClass: BuildRepository::class, formClass: BuildForm::class)]
class Build extends PluginAbstract
{
    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval: true)]
    // #[Assert\Valid()]
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
