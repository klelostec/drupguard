<?php

namespace App\Plugin;

use Symfony\Component\Validator\Exception\InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_CLASS)]
class PluginInfo extends Attribute
{
    /**
     * @var TypeInfo[]
     */
    protected array $types = [];

    /**
     * @return TypeInfo[]
     */
    public function getTypes() :array {
        return $this->types;
    }

    public function addType(TypeInfo $type) :PluginInfo {
        $this->types[$type->getId()] = $type;

        return $this;
    }

    public function getChoices(): array
    {
        $choices = [];
        foreach ($this->types as $type) {
            $choices[$type->getName()] = $type->getId();
        }

        return $choices;
    }
}
