<?php

namespace App\Plugin\Entity;

use App\Entity\Project;
use App\Plugin\Entity\Type\TypeInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface PluginInterface
{
    public function getId(): ?int;

    public function getType(): ?string;

    public function setType(string $type): static;

    public function getProject(): ?Project;

    public function setProject(?Project $project): static;

    public function getTypeEntity(): ?TypeInterface;

    public function validate(ExecutionContextInterface $context): void;

    public function __toString();
}
