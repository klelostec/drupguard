<?php

namespace App\Form\Plugin\Type\Build;

use App\Entity\Plugin\Type\Build\Composer as ComposerEntity;
use App\Form\Plugin\Type\TypeAbstract;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class Composer extends TypeAbstract
{
    public function alterPropertyFormType(string $property, ?string &$type, array &$typeOptions): void
    {
        if ('version' === $property) {
            $type = ChoiceType::class;
            $typeOptions = array_merge($typeOptions, [
                'choice_loader' => new CallbackChoiceLoader(static function (): array {
                    return ComposerEntity::getVersions();
                }),
            ]);
        }
    }
}
