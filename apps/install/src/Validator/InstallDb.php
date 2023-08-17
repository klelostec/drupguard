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
class InstallDb extends Constraint
{
    public string $malformed_dsn_message;
    public string $db_exists_message;
    public string $db_connection_message;

    public function __construct(mixed $options = null, array $groups = null, mixed $payload = null)
    {
        $this->malformed_dsn_message = new TranslatableMessage('Wrong databases connection data provided.', [], 'validators');
        $this->db_exists_message = new TranslatableMessage('Database already exists and contains data.', [], 'validators');
        $this->db_connection_message = new TranslatableMessage('Database connection failed.', [], 'validators');
        parent::__construct($options, $groups, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
