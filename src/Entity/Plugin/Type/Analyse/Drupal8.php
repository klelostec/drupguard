<?php

namespace App\Entity\Plugin\Type\Analyse;

use App\Entity\Plugin\Type\PathTypeAbstract;
use App\Form\Plugin\Type\Analyse\Drupal8 as Drupal8Form;
use App\Plugin\TypeInfo;
use App\Repository\Plugin\Type\Analyse\Drupal8 as Drupal8Repository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'analyse_drupal8')]
#[ORM\Entity(repositoryClass: Drupal8Repository::class)]
#[TypeInfo(id: 'drupal8', name: 'Drupal 8+', type: 'analyse', entityClass: Drupal8::class, repositoryClass: Drupal8Repository::class, formClass: Drupal8Form::class, dependencies: [
    'source' => '*',
    'build' => 'composer',
])]
class Drupal8 extends PathTypeAbstract
{
    public function __toString()
    {
        return 'Drupal 8+'.parent::__toString();
    }
}
