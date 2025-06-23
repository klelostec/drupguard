<?php

namespace App\Plugin\Annotation;

#[\Attribute(\Attribute::TARGET_CLASS)]
class PluginInfo extends Attribute
{
    /**
     * @var TypeInfo[]
     */
    protected array $types = [];
    protected int $weight = 0;

    public function __construct(?array $options = null, ?string $id = null, ?string $name = null, ?string $entityClass = null, ?string $formClass = null, ?string $repositoryClass = null, ?int $weight = 0)
    {
        parent::__construct($options, $id, $name, $entityClass, $formClass, $repositoryClass);
        $this->weight = $weight ?? $this->weight;
    }

    /**
     * @return TypeInfo[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function addType(TypeInfo $type): PluginInfo
    {
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

    public function getWeight(): int {
        return $this->weight;
    }
}
