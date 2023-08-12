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

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DatabaseObjectExistsException;
use Doctrine\DBAL\Exception\MalformedDsnException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BinaryPathValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof BinaryPath) {
            throw new UnexpectedTypeException($constraint, BinaryPath::class);
        }

        try {
            if (!@is_file($value)) {
                $this->context
                    ->buildViolation($constraint->not_found)
                    ->setParameter('{{ binary_path }}', $value)
                    ->setTranslationDomain('validators')
                    ->addViolation();
                return;
            }
            if (!@is_executable($value)) {
                $this->context
                    ->buildViolation($constraint->not_executable)
                    ->setParameter('{{ binary_path }}', $value)
                    ->setTranslationDomain('validators')
                    ->addViolation();
                return;
            }

            if (!empty($constraint->versionValidationRegex) && !empty($constraint->versionArg)) {
                $process = new Process([$value, $constraint->versionArg]);
                if ($constraint->timeout) {
                    $process->setTimeout($constraint->timeout);
                }
                $process->run();
                if (!preg_match('/^' . strtr($constraint->versionValidationRegex, '/', '\\/') . '$/mi', $process->getOutput() ?? '', $matches)) {
                    $this->context
                        ->buildViolation($constraint->wrong_executable)
                        ->setParameter('{{ binary_path }}', $value)
                        ->setTranslationDomain('validators')
                        ->addViolation();
                    return;
                }
                else if (!empty($constraint->versionCompareRegex) && !preg_match('/^' . strtr($constraint->versionCompareRegex, '/', '\\/') . '$/i', $matches[1])) {
                    $this->context
                        ->buildViolation($constraint->wrong_version)
                        ->setParameter('{{ binary_path }}', $value)
                        ->setParameter('{{ version }}', $matches[1])
                        ->setParameter('{{ needed_version }}', str_replace('\.', '.', preg_replace('/(?<!\\\\)\.\*/i', '*', $constraint->versionCompareRegex)))
                        ->setTranslationDomain('validators')
                        ->addViolation();
                }
            }
        }
        catch (\Exception $e) {
            $this->context
                ->buildViolation($constraint->unknown)
                ->setParameter('{{ binary_path }}', $value)
                ->setTranslationDomain('validators')
                ->addViolation();
        }
    }
}
