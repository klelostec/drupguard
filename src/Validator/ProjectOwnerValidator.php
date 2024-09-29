<?php

namespace App\Validator;

use App\Entity\Project;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ProjectOwnerValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProjectOwner) {
            throw new UnexpectedTypeException($constraint, ProjectOwner::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!($value instanceof Project)) {
            throw new UnexpectedValueException($value, 'Project');
        }

        if (!$value->hasOwner()) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('projectMembers')
                ->addViolation();
        }
    }
}
