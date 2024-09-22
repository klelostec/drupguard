<?php

namespace App\Entity;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface SourceSettingsInterface
{
    public function getId(): ?int;
    public function validate(ExecutionContextInterface $context): void;

    public function __toString();
}
