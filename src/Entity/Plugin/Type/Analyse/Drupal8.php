<?php

namespace App\Entity\Plugin\Type\Analyse;

use App\Entity\Plugin\Type\NameTypeInterface;
use App\Entity\Plugin\Type\NameTypeTrait;
use App\Entity\Plugin\Type\PathTypeInterface;
use App\Entity\Plugin\Type\PathTypeTrait;
use App\Entity\Plugin\Type\TypeAbstract;
use App\Repository\Plugin\Type\Analyse\Drupal8 as Drupal8Repository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'analyse_drupal8')]
#[ORM\Entity(repositoryClass: Drupal8Repository::class)]
#[AppAssert\Plugin\Path(checkPathFileSystem: false)]
class Drupal8 extends TypeAbstract implements PathTypeInterface, NameTypeInterface
{
    use NameTypeTrait;
    use PathTypeTrait {
        PathTypeTrait::__toString as traitToString;
    }

    public function __toString()
    {
        return 'Drupal 8+'.$this->traitToString();
    }
}
