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

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\MalformedDsnException;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Tools\DsnParser;
use Install\Entity\Install;
use Install\Service\InstallManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class InstallDbValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof InstallDb) {
            throw new UnexpectedTypeException($constraint, InstallDb::class);
        }
        if (!$value instanceof Install && !is_string($value)) {
            throw new UnexpectedTypeException($value, Install::class);
        }

        try {
            if ($value instanceof Install) {
                $dsnWithDatabase = InstallManager::getDsnParsed($value->getDatabaseDsn());
            }
            else {
                $dsnWithDatabase = InstallManager::getDsnParsed($value);
            }

            $db = $dsnWithDatabase['dbname'] ?? '';
            $dsnWithoutDatabase = $dsnWithDatabase;
            unset($dsnWithoutDatabase['dbname']);

            if (empty($db)) {
                throw new MalformedDsnException();
            }

            $conn = InstallManager::getConnection($dsnWithoutDatabase);
            $databases = $conn->createSchemaManager()->listDatabases();
            if (in_array($db, $databases)) {
                $conn = InstallManager::getConnection($dsnWithDatabase);
                if (!empty($conn->createSchemaManager()->listTables())) {
                    $this->context
                        ->buildViolation($constraint->db_exists_message)
                        ->setTranslationDomain('validators')
                        ->addViolation();
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
