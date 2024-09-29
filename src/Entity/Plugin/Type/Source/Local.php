<?php

namespace App\Entity\Plugin\Type\Source;

use App\Entity\Plugin\Type\PathTypeAbstract;
use App\Form\Plugin\Type\Source\Local as LocalForm;
use App\Plugin\TypeInfo;
use App\Repository\Plugin\Type\Source\Local as LocalRepository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'source_local')]
#[ORM\Entity(repositoryClass: LocalRepository::class)]
#[TypeInfo(id: 'local', name: 'Local', type: 'source', entityClass: Local::class, repositoryClass: LocalRepository::class, formClass: LocalForm::class)]
#[AppAssert\Plugin\Path(allowEmptyPath: false, checkPathFileSystem: true)]
class Local extends PathTypeAbstract
{
    public function __toString()
    {
        return 'Local'.parent::__toString();
    }
}
