<?php

namespace App\Plugin\Entity\Type;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface TypeInterface
{
    public function getId(): ?int;

    public function validate(ExecutionContextInterface $context): void;

    public function __toString();
}
