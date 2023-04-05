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

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\LogicException;

#[\Attribute]
class Conditional extends Constraint
{
    public $propertyPath;
    public $propertyComparisons;
    public $constraints = [];

    public function __construct(string $propertyPath = null, array $propertyComparisons = null, array $constraints = null, array $groups = null, $payload = null, array $options = [])
    {
        $options['propertyPath'] = $propertyPath;
        $options['constraints'] = $constraints;
        $options['propertyComparisons'] = $propertyComparisons;

        if (null !== $groups) {
            $options['groups'] = $groups;
        }

        if (null !== $payload) {
            $options['payload'] = $payload;
        }

        parent::__construct($options);

        $this->propertyPath = $propertyPath ?? $this->propertyPath;
        $this->propertyComparisons = $propertyComparisons ?? $this->propertyComparisons;
        $this->constraints = $constraints ?? $this->constraints;
    }

    public function getRequiredOptions(): array
    {
        return ['propertyPath', 'constraints'];
    }

}
