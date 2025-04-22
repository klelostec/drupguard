<?php

namespace App\Plugin\Service\Type\Analyse;

use App\Entity\Plugin\Type\Analyse\Drupal8 as Drupal8Entity;
use App\Entity\Project;
use App\Form\Plugin\Type\Analyse\Drupal8 as Drupal8Form;
use App\Plugin\Annotation\TypeInfo;
use App\Plugin\Service\Analyse;
use App\Repository\Plugin\Type\Analyse\Drupal8 as Drupal8Repository;
use Symfony\Component\Translation\TranslatableMessage;

#[TypeInfo(
    id: 'drupal8',
    name: 'Drupal 8+',
    type: 'analyse',
    entityClass: Drupal8Entity::class,
    repositoryClass: Drupal8Repository::class,
    formClass: Drupal8Form::class,
    help: new TranslatableMessage('Drupal security releases happen between 16:00 UTC and 22:00 UTC every Wednesday, so the value <em>0 5 * * 4</em> is suggested.'),
    dependencies: [
        'source' => '*',
        'build' => 'composer',
    ]
)]
class Drupal8 extends Analyse
{
    public function analyse(Project $project, mixed $analyse, string $path): mixed
    {
    }
}
