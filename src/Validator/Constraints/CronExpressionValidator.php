<?php

namespace App\Validator\Constraints;

use Cron\CronExpression as CronExpressionLib;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class CronExpressionValidator.
 */
class CronExpressionValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof CronExpression) {
            throw new UnexpectedTypeException($constraint, CronExpression::class);
        }

        if (empty($value) || !CronExpressionLib::isValidExpression($value)) {
            $this->context->buildViolation($constraint->message)
              ->setParameter('{{ value }}', $this->formatValue($value))
              ->setCode(CronExpression::NOT_CRON_EXPRESSION)
              ->addViolation();
        }
    }
}
