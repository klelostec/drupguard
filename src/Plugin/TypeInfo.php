<?php

namespace App\Plugin;

class TypeInfo {
    protected string $id;
    protected string $name;
    protected ?string $entity;
    protected ?string $form;
    protected ?string $repository;

    public function __construct(string $id, string $name, string $entity = null, string $form = null, string $repository = null) {
        $this->id = $id;
        $this->name = $name;
        $this->entity = $entity;
        $this->form = $form;
        $this->repository = $repository;
    }
    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): TypeInfo
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): TypeInfo
    {
        $this->name = $name;
        return $this;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): TypeInfo
    {
        $this->entity = $entity;
        return $this;
    }

    public function getForm(): string
    {
        return $this->form;
    }

    public function setForm(string $form): TypeInfo
    {
        $this->form = $form;
        return $this;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    public function setRepository(string $repository): TypeInfo
    {
        $this->repository = $repository;
        return $this;
    }
}