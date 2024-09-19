<?php

namespace App\EasyAdmin\Field;

use App\Form\Type\MachineNameType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Contracts\Translation\TranslatableInterface;

final class MachineNameField implements FieldInterface
{
    use FieldTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        $assetPackage = new Package(new EmptyVersionStrategy());
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(MachineNameType::class)
            ->setDefaultColumns('col-md-6 col-xxl-5')
            ->addCssClass('field-machine-name')
        ;
    }
}