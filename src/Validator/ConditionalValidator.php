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
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\AbstractComparison;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ConditionalValidator extends ConstraintValidator
{
    private ?PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor ??= PropertyAccess::createPropertyAccessor();
    }

    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof Conditional) {
            throw new UnexpectedTypeException($constraint, Conditional::class);
        }

        if ($path = $constraint->propertyPath) {
            if (null === $object = $this->context->getRoot()) {
                return;
            }

            try {
                $comparedValue = $this->getPropertyAccessor()->getValue($object->getData(), '[' . $path . ']');

                if (
                    (is_scalar($constraint->propertyComparisons) && $comparedValue === $comparedValue) ||
                    (is_array($constraint->propertyComparisons) && in_array($comparedValue, $constraint->propertyComparisons, TRUE))
                ) {
                    $context = $this->context;
                    $context->getValidator()->inContext($context)
                        ->validate($value, $constraint->constraints);
                }

            } catch (NoSuchPropertyException $e) {
                throw new ConstraintDefinitionException(sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, get_debug_type($constraint)) . $e->getMessage(), 0, $e);
            }
        }
    }
}
