<?php

namespace App\Entity\Plugin\Type\Analyse;

use App\Entity\Plugin\Type\PathTypeAbstract;
use App\Form\Plugin\Type\Analyse\ComposerAudit as ComposerAuditForm;
use App\Plugin\TypeInfo;
use App\Repository\Plugin\Type\Analyse\ComposerAudit as ComposerAuditRepository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'analyse_composer_audit')]
#[ORM\Entity(repositoryClass: ComposerAuditRepository::class)]
#[TypeInfo(id: 'composer_audit', name: 'Composer audit', type: 'analyse', entityClass: ComposerAudit::class, repositoryClass: ComposerAuditRepository::class, formClass: ComposerAuditForm::class, dependencies: [
    'source' => '*',
    'build' => 'composer',
])]
#[AppAssert\Plugin\Path()]
class ComposerAudit extends PathTypeAbstract
{
    public function __toString()
    {
        return 'Composer audit'.parent::__toString();
    }
}
