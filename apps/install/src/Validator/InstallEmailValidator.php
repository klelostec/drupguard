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

use Install\Entity\Install;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class InstallEmailValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof InstallEmail) {
            throw new UnexpectedTypeException($constraint, InstallEmail::class);
        }
        if (!$value instanceof Install && !is_string($value)) {
            throw new UnexpectedTypeException($value, Install::class);
        }

        try {
            $dsn = is_string($value) ? $value : $value->getEmailDsn();
            Transport::fromDsn($dsn);
        }
        catch (\Exception $e) {
            if ($e instanceof InvalidArgumentException) {
                $this->context
                    ->buildViolation($constraint->malformed_dsn_message)
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }
            else {
                $this->context
                    ->buildViolation($constraint->unknown_error)
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }

        }
    }
}
