<?php

namespace App\Validator\Plugin;

use App\Entity\Plugin\Type\PathTypeAbstract;
use App\Plugin\Service\Manager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PathValidator extends ConstraintValidator
{
    protected Manager $pluginManager;

    public function __construct(Manager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Path) {
            throw new UnexpectedTypeException($constraint, Path::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!($value instanceof PathTypeAbstract)) {
            throw new UnexpectedValueException($value, 'PathTypeAbstract');
        }

        $path = $value->getPath();
        if (empty($path)) {
            if (!$constraint->allowEmptyPath) {
                $this->context
                    ->getValidator()
                    ->inContext($this->context)
                    ->atPath('path')
                    ->validate($path, new NotBlank())
                ;
            }

            return;
        }

        $filesystem = new Filesystem();
        if (
            !$filesystem->isAbsolutePath($path)
            || \DIRECTORY_SEPARATOR === mb_substr($path, -1)
        ) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('path')
                ->addViolation();
        } elseif (
            $constraint->checkPathFileSystem && (
                !is_dir($path)
                || !$filesystem->exists($path)
                || !is_readable($path)
            )
        ) {
            $this->context
                ->buildViolation($constraint->messageFileSystem)
                ->atPath('path')
                ->addViolation();
        }
    }
}
