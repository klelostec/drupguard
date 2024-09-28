<?php

namespace App\Plugin;

use Symfony\Component\Validator\Exception\InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_CLASS)]
abstract class Attribute
{
    protected string $id ='';
    protected string $name = '';
    protected string $entityClass = '';
    protected string $formClass = '';
    protected string $repositoryClass = '';

    public function __construct(?array $options = null, ?string $id = null, ?string $name = null, ?string $entityClass = null, ?string $formClass = null, ?string $repositoryClass = null)
    {
        foreach ($options ?? [] as $name => $value) {
            $this->$name = $value;
        }
        $this->id = $id ?? $this->id;
        $this->name = $name ?? $this->name;
        $this->entityClass = $entityClass ?? $this->entityClass;
        $this->formClass = $formClass ?? $this->formClass;
        $this->repositoryClass = $repositoryClass ?? $this->repositoryClass;

        foreach (['id', 'name', 'entityClass', 'formClass', 'repositoryClass'] as $field) {
            if (empty($this->{$field})) {
                throw new InvalidArgumentException(sprintf('The "%s" option is required.', $field));
            }
        }

        foreach (['entityClass', 'formClass', 'repositoryClass'] as $field) {
            if (!class_exists($this->{$field})) {
                throw new InvalidArgumentException(sprintf('The "%s" class not exists for "%s" property.', $this->{$field}, $field));
            }
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getFormClass(): string
    {
        return $this->formClass;
    }

    public function getRepositoryClass(): string
    {
        return $this->repositoryClass;
    }
}
