<?php

namespace App\Plugin;

use Symfony\Component\Validator\Exception\InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_CLASS)]
class TypeInfo extends Attribute
{
    protected array $dependencies = [];
    protected string $type;

    public function __construct(?array $options = null, ?string $id = null, ?string $name = null, ?string $type = null, ?string $entityClass = null, ?string $formClass = null, ?string $repositoryClass = null, ?array $dependencies = null)
    {
        parent::__construct($options, $id, $name, $entityClass, $formClass, $repositoryClass);

        $this->type = $type ?? $this->type;
        $this->dependencies = $dependencies ?? $this->dependencies;

        if (empty($this->type)) {
            throw new InvalidArgumentException(sprintf('The "type" option is required.'));
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
