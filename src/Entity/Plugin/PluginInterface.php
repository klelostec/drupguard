<?php

namespace App\Entity\Plugin;

use App\Entity\Plugin\Type\TypeInterface;
use App\Entity\Project;

interface PluginInterface
{
    public function getId(): ?int;

    public function getType(): ?string;

    public function setType(string $type): static;

    public function getProject(): ?Project;

    public function setProject(?Project $project): static;

    public function getTypeEntity(): ?TypeInterface;

    public function __toString();
}
