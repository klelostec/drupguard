<?php

namespace App\Entity\Plugin;

use App\Entity\Plugin\Type\Analyse\ComposerAudit;
use App\Entity\Plugin\Type\Analyse\Drupal7;
use App\Entity\Plugin\Type\Analyse\Drupal8;
use App\Form\Plugin\Analyse as AnalyseForm;
use App\Plugin\PluginInfo;
use App\Repository\Plugin\Analyse as AnalyseRepository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnalyseRepository::class)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(name: 'project', inversedBy: 'analysePlugins'),
])]
#[PluginInfo(id: 'analyse', name: 'Analyse', entityClass: Analyse::class, repositoryClass: AnalyseRepository::class, formClass: AnalyseForm::class)]
#[AppAssert\Plugin\Plugin()]
class Analyse extends PluginAbstract
{
    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval: true)]
    // #[Assert\Valid()]
    protected ?ComposerAudit $composerAudit = null;
    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval: true)]
    // #[Assert\Valid()]
    protected ?Drupal7 $drupal7 = null;
    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval: true)]
    // #[Assert\Valid()]
    protected ?Drupal8 $drupal8 = null;

    public function getDrupal7(): ?Drupal7
    {
        return $this->drupal7;
    }

    public function setDrupal7(?Drupal7 $drupal7): static
    {
        $this->drupal7 = $drupal7;

        return $this;
    }

    public function getDrupal8(): ?Drupal8
    {
        return $this->drupal8;
    }

    public function setDrupal8(?Drupal8 $drupal8): static
    {
        $this->drupal8 = $drupal8;

        return $this;
    }

    public function getComposerAudit(): ?ComposerAudit
    {
        return $this->composerAudit;
    }

    public function setComposerAudit(?ComposerAudit $composerAudit): static
    {
        $this->composerAudit = $composerAudit;

        return $this;
    }
}
