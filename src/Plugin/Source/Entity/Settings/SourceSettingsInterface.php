<?php

namespace App\Plugin\Source\Entity\Settings;

use App\Plugin\Source\Entity\SourcePlugin;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface SourceSettingsInterface
{
    public function getId(): ?int;

    public function validate(ExecutionContextInterface $context): void;

    public function __toString();
}
