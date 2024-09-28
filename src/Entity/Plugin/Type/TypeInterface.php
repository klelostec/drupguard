<?php

namespace App\Entity\Plugin\Type;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface TypeInterface
{
    public function getId(): ?int;

    public function validate(ExecutionContextInterface $context): void;

    public function __toString();
}
