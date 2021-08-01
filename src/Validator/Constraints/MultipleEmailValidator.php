<?php

namespace App\Validator\Constraints;

use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Egulias\EmailValidator\EmailValidator as EguliasEmailValidator;

/**
 * Class MultipleEmailValidator.
 */
class MultipleEmailValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof MultipleEmail) {
            throw new UnexpectedTypeException($constraint, MultipleEmail::class);
        }

        if (!empty($value)) {
            $value = str_replace("\r\n", "\n", $value);
            $values = explode("\n", $value);
            $strictValidator = new EguliasEmailValidator();
            foreach ($values as $email) {
                if (!$strictValidator->isValid($email, new NoRFCWarningsValidation())) {
                    $this->context->buildViolation($constraint->message)
                      ->setParameter('{{ value }}', $this->formatValue($value))
                      ->setCode(MultipleEmail::MULTIPLE_EMAIL_ERROR)
                      ->addViolation();
                    break;
                }
            }
        }
    }
}
