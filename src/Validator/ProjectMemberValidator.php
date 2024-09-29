<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ProjectMemberValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProjectMember) {
            throw new UnexpectedTypeException($constraint, ProjectMember::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!($value instanceof \App\Entity\ProjectMember)) {
            throw new UnexpectedValueException($value, 'ProjectMember');
        }

        if (
            (null !== $value->getGroups() && null !== $value->getUser())
            || (null === $value->getGroups() && null === $value->getUser())
        ) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('groups')
                ->addViolation();
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('user')
                ->addViolation();
        }
    }
}
