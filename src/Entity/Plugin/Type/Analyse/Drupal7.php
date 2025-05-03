<?php

namespace App\Entity\Plugin\Type\Analyse;

use App\Entity\Plugin\Type\NameTypeInterface;
use App\Entity\Plugin\Type\NameTypeTrait;
use App\Entity\Plugin\Type\PathTypeInterface;
use App\Entity\Plugin\Type\PathTypeTrait;
use App\Entity\Plugin\Type\TypeAbstract;
use App\Repository\Plugin\Type\Analyse\Drupal7 as Drupal7Repository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'analyse_drupal7')]
#[ORM\Entity(repositoryClass: Drupal7Repository::class)]
#[AppAssert\Plugin\Path(checkPathFileSystem: false)]
class Drupal7 extends TypeAbstract implements PathTypeInterface, NameTypeInterface
{
    use NameTypeTrait;
    use PathTypeTrait {
        PathTypeTrait::__toString as traitToString;
    }

    public function __toString()
    {
        return 'Drupal 7'.$this->traitToString();
    }
}
