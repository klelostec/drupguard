<?php

namespace App\Entity\Plugin\Type;

interface NameTypeInterface
{
    public function getName(): ?string;

    public function setName(?string $name): static;
}
