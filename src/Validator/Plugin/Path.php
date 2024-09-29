<?php

namespace App\Validator\Plugin;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Path extends Constraint
{
    public string $message = 'Path is not valid, it must be absolute and must not end with a "'.\DIRECTORY_SEPARATOR.'".';
    public string $messageFileSystem = 'Directory not exists or is not readable.';

    public bool $allowEmptyPath = true;
    public bool $checkPathFileSystem = false;

    #[HasNamedArguments]
    public function __construct(
        ?array $options = null,
        ?bool $allowEmptyPath = null,
        ?bool $checkPathFileSystem = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options ?? [], $groups, $payload);
        $this->allowEmptyPath = $allowEmptyPath ?? $this->allowEmptyPath;
        $this->checkPathFileSystem = $checkPathFileSystem ?? $this->checkPathFileSystem;
    }

    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
