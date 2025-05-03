<?php

namespace App\Entity\Plugin\Type;

interface PathTypeInterface
{
    public function getPath(): ?string;

    public function setPath(?string $path): static;
}
