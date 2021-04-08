<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class GitRemote extends Constraint
{
    public const GIT_STRING_ERROR = '7687da9e-2d3f-4578-9963-63679f1434ea';
    public const GIT_READ_ERROR = '8165d0dc-934c-4cda-bbc7-9bddc61571d9';

    protected static $errorNames = [
      self::GIT_STRING_ERROR => 'GIT_STRING_ERROR',
      self::GIT_READ_ERROR => 'GIT_READ_ERROR',
    ];

    public $stringMessage = 'String is not a git remote url valid.';
    public $readMessage = 'Cannot read source from repository.';

    public function __construct(array $options = null, string $stringMessage = null, string $readMessage = null, array $groups = null, $payload = null)
    {
        parent::__construct($options, $groups, $payload);

        $this->stringMessage = $stringMessage ?? $this->stringMessage;
        $this->readMessage = $readMessage ?? $this->readMessage;
    }
}
