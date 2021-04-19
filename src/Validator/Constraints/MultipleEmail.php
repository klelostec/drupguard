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
class MultipleEmail extends Constraint
{
    public const MULTIPLE_EMAIL_ERROR = 'a19bd1fe-4082-4363-908a-5bae2b6b5168';

    protected static $errorNames = [
      self::MULTIPLE_EMAIL_ERROR => 'MULTIPLE_EMAIL_ERROR',
    ];

    public $message = 'The field contain email not valid.';

    public function __construct(array $options = null, string $message = null, array $groups = null, $payload = null)
    {
        parent::__construct($options, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
