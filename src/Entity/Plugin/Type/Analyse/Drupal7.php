<?php

namespace App\Entity\Plugin\Type\Analyse;

use App\Entity\Plugin\Type\PathTypeAbstract;
use App\Form\Plugin\Type\Analyse\Drupal7 as Drupal7Form;
use App\Plugin\TypeInfo;
use App\Repository\Plugin\Type\Analyse\Drupal7 as Drupal7Repository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'analyse_drupal7')]
#[ORM\Entity(repositoryClass: Drupal7Repository::class)]
#[TypeInfo(id: 'drupal7', name: 'Drupal 7', type: 'analyse', entityClass: Drupal7::class, repositoryClass: Drupal7Repository::class, formClass: Drupal7Form::class, dependencies: [
    'source' => '*',
])]
class Drupal7 extends PathTypeAbstract
{
    public function __toString()
    {
        return 'Drupal 7'.parent::__toString();
    }
}
