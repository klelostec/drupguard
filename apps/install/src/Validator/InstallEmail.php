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
 * @Target({"CLASS"})
 *
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class InstallEmail extends Constraint
{
    public string $malformed_dsn_message;
    public string $unknown_error;

    public function __construct(mixed $options = null, array $groups = null, mixed $payload = null)
    {
        $this->malformed_dsn_message = new TranslatableMessage('Wrong mailer data provided.', [], 'validators');
        $this->unknown_error = new TranslatableMessage('Unknown error.', [], 'validators');
        parent::__construct($options, $groups, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
