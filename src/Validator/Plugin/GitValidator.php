<?php

namespace App\Validator\Plugin;

use App\Plugin\Service\Manager;
use CzProject\GitPhp\Git as GitClient;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class GitValidator extends ConstraintValidator
{
    protected Manager $pluginManager;

    public function __construct(Manager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Git) {
            throw new UnexpectedTypeException($constraint, Git::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!($value instanceof \App\Entity\Plugin\Type\Source\Git)) {
            throw new UnexpectedValueException($value, 'Git');
        }

        if (empty($value->getRepository())) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->atPath('repository')
                ->validate($value->getRepository(), new NotBlank())
            ;
        }

        if (empty($value->getBranch())) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->atPath('branch')
                ->validate($value->getBranch(), new NotBlank())
            ;
        }

        $git = new GitClient();
        if (!empty($value->getRepository()) && !$git->isRemoteUrlReadable($value->getRepository(), [])) {
            $this->context
                ->buildViolation($constraint->messageRepository)
                ->atPath('repository')
                ->addViolation();
        }
        if (!empty($value->getRepository()) && !empty($value->getBranch()) && !$git->isRemoteUrlReadable($this->repository, [$this->branch])) {
            $this->context
                ->buildViolation($constraint->messageBranch)
                ->atPath('branch')
                ->addViolation();
        }
    }
}
