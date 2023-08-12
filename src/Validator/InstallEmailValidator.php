<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator;

use App\Entity\Install;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class InstallEmailValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof InstallEmail) {
            throw new UnexpectedTypeException($constraint, InstallEmail::class);
        }

        try {
            static::buildTransport($value);
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

    public static function buildTransport(Install $value):Transport\TransportInterface {
        $dsn = $value->email_type_install . '://';
        switch ($value->email_type_install) {
            case 'sendmail':
                $dsn .= 'default';
                if (!empty($value->email_command)) {
                    $dsn .= '?command=' . urlencode($value->email_command);
                }
                break;
            case 'smtp':
            case 'smtps':
                if (!empty($value->email_user)) {
                    $dsn .= urlencode($value->email_user) .
                        ($value->email_password ? ':' . urlencode($value->email_password) : '') .
                        '@';
                }
                if (!empty($value->email_host)) {
                    $dsn .= $value->email_host;
                }

                $paramsKeys = [
                    'local_domain',
                    'restart_threshold',
                    'restart_threshold_sleep',
                    'ping_threshold',
                    'max_per_second'
                ];

                $dsnParams = '';
                foreach ($paramsKeys as $k => $v) {
                    if (!empty($value->{'email_' . $k})) {
                        $dsnParams .= $k . '=' . urlencode($value->{'email_' . $k});
                    }
                }
                if (!empty($dsnParams)) {
                    $dsn .= '?' . $dsnParams;
                }
                break;
            case 'custom':
                $dsn = $value->email_dsn_custom ?? '';
                break;
            case 'null':
                $dsn .= 'null';
                break;
            case 'native':
            default:
                $dsn .= 'default';
                break;
        }

        return Transport::fromDsn($dsn);
    }
}
