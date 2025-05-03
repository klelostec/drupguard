<?php

namespace App\Entity\Plugin\Type\Source;

use App\Entity\Plugin\Type\PathTypeInterface;
use App\Entity\Plugin\Type\PathTypeTrait;
use App\Entity\Plugin\Type\TypeAbstract;
use App\Repository\Plugin\Type\Source\Local as LocalRepository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'source_local')]
#[ORM\Entity(repositoryClass: LocalRepository::class)]
#[AppAssert\Plugin\Path(allowEmptyPath: false, checkPathFileSystem: true)]
class Local extends TypeAbstract implements PathTypeInterface
{
    use PathTypeTrait {
        PathTypeTrait::__toString as traitToString;
    }

    public function __toString()
    {
        return 'Local'.$this->traitToString();
    }
}
