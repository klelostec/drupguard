<?php

namespace App\Validator\Plugin;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Dependencies extends Constraint {
    public string $messagePlugin = 'Plugin type "{{ type }}" needs at least one item in "{{ dependencyPlugin }}" section.';
    public string $messageType = 'Plugin type "{{ type }}" needs plugin type "{{ dependencyType }}" in "{{ dependencyPlugin }}" section.';

    #[HasNamedArguments]
    public function __construct(
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}