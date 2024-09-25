<?php

namespace App\Plugin\Form\Type\Build;

use App\Plugin\Form\Type\TypeAbstract;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class Composer extends TypeAbstract
{
    public function alterPropertyFormType(string $property, ?string &$type, array &$typeOptions) :void {
        if ($property === 'version') {
            $type = ChoiceType::class;
            $typeOptions = array_merge($typeOptions, [
                'choice_loader' => new CallbackChoiceLoader(static function (): array {
                    return \App\Plugin\Entity\Type\Build\Composer::getVersions();
                }),
            ]);
        }
    }
}
