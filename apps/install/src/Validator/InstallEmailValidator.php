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
        if (!$value instanceof Install) {
            throw new UnexpectedTypeException($value, Install::class);
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
        $dsn = $value->getEmailTypeInstall() . '://';
        switch ($value->getEmailTypeInstall()) {
            case 'sendmail':
                $dsn .= 'default';
                if (!empty($value->getEmailCommand())) {
                    $dsn .= '?command=' . urlencode($value->getEmailCommand());
                }
                break;
            case 'smtp':
            case 'smtps':
                if (!empty($value->getEmailUser())) {
                    $dsn .= urlencode($value->getEmailUser()) .
                        ($value->getEmailPassword() ? ':' . urlencode($value->getEmailPassword()) : '') .
                        '@';
                }
                if (!empty($value->getEmailHost())) {
                    $dsn .= $value->getEmailHost();
                }

                $paramsKeys = [
                    'local_domain' => 'getEmailLocalDomain',
                    'restart_threshold' => 'getEmailRestartThreshold',
                    'restart_threshold_sleep' => 'getEmailRestartThresholdSleep',
                    'ping_threshold' => 'getEmailPingThreshold',
                    'max_per_second' => 'getEmailMaxPerSecond'
                ];
                $dsnParams = '';
                foreach ($paramsKeys as $k => $v) {
                    if (is_callable([$value, $v]) && !empty($paramValue = call_user_func([$value, $v]))) {
                        $dsnParams .= $k . '=' . urlencode($paramValue);
                    }
                }
                if (!empty($dsnParams)) {
                    $dsn .= '?' . $dsnParams;
                }
                break;
            case 'custom':
                $dsn = $value->getEmailDsnCustom() ?? '';
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
