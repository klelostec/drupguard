<?php

namespace App\Entity\Plugin\Type\Analyse;

use App\Entity\Plugin\Type\PathTypeAbstract;
use App\Form\Plugin\Type\Analyse\Drupal7 as Drupal7Form;
use App\Plugin\TypeInfo;
use App\Repository\Plugin\Type\Analyse\Drupal7 as Drupal7Repository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Translation\TranslatableMessage;
use function Symfony\Component\Translation\t;

#[ORM\Table(name: 'analyse_drupal7')]
#[ORM\Entity(repositoryClass: Drupal7Repository::class)]
#[TypeInfo(id: 'drupal7', name: 'Drupal 7', type: 'analyse', entityClass: Drupal7::class, repositoryClass: Drupal7Repository::class, formClass: Drupal7Form::class,
    help: new TranslatableMessage('Drupal security releases happen between 16:00 UTC and 22:00 UTC every Wednesday, so the value <em>0 5 * * 4</em> is suggested.'),
    dependencies: [
        'source' => '*',
    ]
)]
#[AppAssert\Plugin\Path()]
class Drupal7 extends PathTypeAbstract
{
    public function __toString()
    {
        return 'Drupal 7'.parent::__toString();
    }
}
