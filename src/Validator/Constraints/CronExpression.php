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
class CronExpression extends Constraint
{
    public const NOT_CRON_EXPRESSION = 'c929b8f4-45a1-49e9-920b-25d56baf279f';

    protected static $errorNames = [
      self::NOT_CRON_EXPRESSION => 'NOT_CRON_EXPRESSION',
    ];

    public $message = 'This value should be a cron expression.';

    public function __construct(array $options = null, string $message = null, array $groups = null, $payload = null)
    {
        parent::__construct($options, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
