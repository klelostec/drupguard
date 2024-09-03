<?php

namespace App\EasyAdmin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatableInterface;

final class MachineNameField implements FieldInterface
{
    use FieldTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(TextType::class)
            ->addCssClass('field-machine-name')
            ->setDefaultColumns('col-md-6 col-xxl-5')
            ->addCssFiles('styles/admin/modules/machine_name.css')
            ->addJsFiles('machine_name.js')
            ;
    }
}