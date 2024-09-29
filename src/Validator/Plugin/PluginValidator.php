<?php

namespace App\Validator\Plugin;

use App\Entity\Plugin\PluginInterface;
use App\Plugin\Service\Manager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PluginValidator extends ConstraintValidator
{
    protected Manager $pluginManager;

    public function __construct(Manager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Plugin) {
            throw new UnexpectedTypeException($constraint, Plugin::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!($value instanceof PluginInterface)) {
            throw new UnexpectedValueException($value, 'PluginInterface');
        }

        if (empty($value->getType())) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->atPath('type')
                ->validate($value->getType(), new NotBlank())
            ;

            return;
        }

        $typePlugin = $value->getTypeEntity();
        if (empty($typePlugin)) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->atPath($value->getType())
                ->validate($typePlugin, new NotBlank())
            ;
        } else {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->atPath($value->getType())
                ->validate($typePlugin, new Valid())
            ;
        }
    }
}
