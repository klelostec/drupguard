<?php

namespace App\Plugin;

class PluginInfo extends TypeInfo
{
    /**
     * @var TypeInfo[]
     */
    protected array $types;

    public function __construct(string $id, string $name, ?string $entity = null, ?string $form = null, ?string $repository = null)
    {
        parent::__construct($id, $name, $entity, $form, $repository);
        $this->types = [];
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getType(string $id): ?TypeInfo
    {
        return $this->types[$id] ?? null;
    }

    public function setTypes(array $types): PluginInfo
    {
        $this->types = $types;

        return $this;
    }

    public function addType(TypeInfo $typeInfo): PluginInfo
    {
        $this->types[$typeInfo->getId()] = $typeInfo;

        return $this;
    }

    public function removeType(string $type): PluginInfo
    {
        unset($this->types[$type]);

        return $this;
    }

    public function getChoices(): array
    {
        $choices = ['-- Choose ' => ''];
        foreach ($this->types as $type) {
            $choices[$type->getName()] = $type->getId();
        }

        return $choices;
    }
}
