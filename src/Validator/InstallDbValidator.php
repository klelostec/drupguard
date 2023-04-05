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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class InstallDbValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof InstallDb) {
            throw new UnexpectedTypeException($constraint, InstallDb::class);
        }

        try {
            $db_url = $value['driver'] . '://' .
                urlencode($value['user']) . ':' .
                urlencode($value['password']) . '@' .
                urlencode($value['host']);
            $conn = DriverManager::getConnection(['url' => $db_url]);
            $databases = $conn->createSchemaManager()->listDatabases();
            if (in_array($value['database'], $databases)) {
                $conn = DriverManager::getConnection(['url' => $db_url . '/' . $value['database']]);
                if (!empty($conn->createSchemaManager()->listTables())) {
                    throw new DatabaseObjectExistsException();
                }
            }
        }
        catch (\Exception $e) {
            if ($e instanceof MalformedDsnException) {
                $this->context
                    ->buildViolation($constraint->malformed_dsn_message)
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }
            if ($e instanceof DatabaseObjectExistsException) {
                $this->context
                    ->buildViolation($constraint->db_exists_message)
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }
            else {
                $this->context
                    ->buildViolation($constraint->db_connection_message)
                    ->setTranslationDomain('validators')
                    ->atPath('host')
                    ->addViolation();
            }

        }
    }
}
