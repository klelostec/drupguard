<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Install\Validator;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "PROPERTY", "METHOD", "ANNOTATION"})
 *
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BinaryPath extends Constraint
{
    public int $timeout;
    public $versionValidationRegex;
    public $versionCompareRegex;
    public $versionArg;

    public string $not_executable;
    public string $wrong_executable;
    public string $wrong_version;
    public string $not_found;
    public string $unknown;

    public function __construct(
        int|array $timeout = null,
        string $versionArg = null,
        string $versionValidationRegex = null,
        string $versionCompareRegex = null,
        array $groups = null,
        mixed $payload = null,
        array $options = []
    ) {
        if (\is_array($timeout)) {
            $options = array_merge($timeout, $options);
            $timeout = $options['timeout'] ?? 0;
        }
        $versionArg ??= $options['versionArg'] ?? '--version';
        $versionValidationRegex ??= $options['versionValidationRegex'] ?? null;
        $versionCompareRegex ??= $options['versionCompareRegex'] ?? null;

        unset($options['timeout'], $options['versionArg'], $options['versionValidationRegex'], $options['versionCompareRegex']);

        parent::__construct($options, $groups, $payload);

        $this->timeout = $timeout;
        $this->versionArg = $versionArg;
        $this->versionValidationRegex = $versionValidationRegex;
        $this->versionCompareRegex = $versionCompareRegex;

        $this->not_found = new TranslatableMessage('Binary "{{ binary_path }}" not found.', [], 'validators');
        $this->not_executable = new TranslatableMessage('Binary "{{ binary_path }}" is not executable.', [], 'validators');
        $this->wrong_executable = new TranslatableMessage('Binary "{{ binary_path }}" does not match expected binary.', [], 'validators');
        $this->wrong_version = new TranslatableMessage('Binary "{{ binary_path }}" has a wrong version. Binary has version "{{ version }}" instead of "{{ needed_version }}".', [], 'validators');
        $this->unknown = new TranslatableMessage('Unknown error during binary "{{ binary_path }}" validation.', [], 'validators');
    }

}
