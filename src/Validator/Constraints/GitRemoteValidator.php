<?php

namespace App\Validator\Constraints;

use App\Service\GitHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class GitRemoteValidator.
 */
class GitRemoteValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof GitRemote) {
            throw new UnexpectedTypeException($constraint, GitRemote::class);
        }

        if (empty($value) || !preg_match('#((git|ssh|http(s)?)|(git@[\w\.\-\_]+))(:(//)?)([\w\.@\:/\-~]+)(\.git)(/)?#i', $value)) {
            $this->context->buildViolation($constraint->stringMessage)
              ->setParameter('{{ value }}', $this->formatValue($value))
              ->setCode(GitRemote::GIT_STRING_ERROR)
              ->addViolation();
        } elseif (!GitHelper::isRemoteUrlReadable($value)) {
            $this->context->buildViolation($constraint->readMessage)
              ->setParameter('{{ value }}', $this->formatValue($value))
              ->setCode(GitRemote::GIT_READ_ERROR)
              ->addViolation();
        }
    }
}
