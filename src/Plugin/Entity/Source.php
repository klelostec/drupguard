<?php

namespace App\Plugin\Entity;

use App\Plugin\Entity\Type\Source\Git;
use App\Plugin\Entity\Type\Source\Local;
use App\Plugin\Repository\Source as SourceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(name: 'project', inversedBy: 'sourcePlugins')
])]
class Source extends PluginAbstract
{
    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval:true)]
    //#[Assert\Valid()]
    protected ?Local $local = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval:true)]
    //#[Assert\Valid()]
    protected ?Git $git = null;

    public function getLocal(): ?Local
    {
        return $this->local;
    }

    public function setLocal(?Local $local): static
    {
        $this->local = $local;

        return $this;
    }

    public function getGit(): ?Git
    {
        return $this->git;
    }

    public function setGit(?Git $git): static
    {
        $this->git = $git;

        return $this;
    }

}
