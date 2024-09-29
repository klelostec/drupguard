<?php

namespace App\Entity\Plugin\Type;

interface TypeInterface
{
    public function getId(): ?int;

    public function __toString();
}
