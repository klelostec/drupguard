<?php

namespace App\Entity\Plugin\Type\Analyse;

use App\Entity\Plugin\Type\PathTypeAbstract;
use App\Form\Plugin\Type\Analyse\Drupal8 as Drupal8Form;
use App\Plugin\TypeInfo;
use App\Repository\Plugin\Type\Analyse\Drupal8 as Drupal8Repository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Table(name: 'analyse_drupal8')]
#[ORM\Entity(repositoryClass: Drupal8Repository::class)]
#[TypeInfo(id: 'drupal8', name: 'Drupal 8+', type: 'analyse', entityClass: Drupal8::class, repositoryClass: Drupal8Repository::class, formClass: Drupal8Form::class,
    help: new TranslatableMessage('Drupal security releases happen between 16:00 UTC and 22:00 UTC every Wednesday, so the value <em>0 5 * * 4</em> is suggested.'),
    dependencies: [
        'source' => '*',
        'build' => 'composer',
    ]
)]
#[AppAssert\Plugin\Path()]
class Drupal8 extends PathTypeAbstract
{
    public function __toString()
    {
        return 'Drupal 8+'.parent::__toString();
    }
}
