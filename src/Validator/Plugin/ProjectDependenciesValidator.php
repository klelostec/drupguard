<?php

namespace App\Validator\Plugin;

use App\Entity\Project;
use App\Plugin\Service\Manager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function Symfony\Component\String\u;

class ProjectDependenciesValidator extends ConstraintValidator
{
    protected Manager $pluginManager;

    public function __construct(Manager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProjectDependencies) {
            throw new UnexpectedTypeException($constraint, ProjectDependencies::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!($value instanceof Project)) {
            throw new UnexpectedValueException($value, 'Project');
        }

        $typesDef = $this->pluginManager->getTypes();
        $pluginsDef = $this->pluginManager->getPlugins();
        $currentMap = [];
        foreach ($pluginsDef as $pluginInfo) {
            $collection = $value->{'get'.mb_ucfirst(u($pluginInfo->getId())->camel()).'Plugins'}();
            $currentMap[$pluginInfo->getId()] = [];
            foreach ($collection as $pluginEntity) {
                if (empty($pluginEntity->getType())) {
                    continue;
                }
                $currentMap[$pluginInfo->getId()][] = $pluginEntity->getType();
            }
        }

        foreach ($currentMap as $pluginId => $types) {
            foreach ($types as $type) {
                foreach ($typesDef[$type]->getDependencies() as $dependencyType => $dependency) {
                    if ('*' === $dependency && 0 === count($currentMap[$dependencyType] ?? [])) {
                        $this->context
                            ->buildViolation($constraint->messagePlugin)
                            ->atPath($pluginId.'Plugins')
                            ->setParameter('{{ dependencyPlugin }}', $pluginsDef[$dependencyType]->getName())
                            ->setParameter('{{ type }}', $typesDef[$type]->getName())
                            ->addViolation();
                    } elseif ('*' !== $dependency && !in_array($dependency, $currentMap[$dependencyType])) {
                        $this->context
                            ->buildViolation($constraint->messageType)
                            ->atPath($pluginId.'Plugins')
                            ->setParameter('{{ dependencyPlugin }}', $pluginsDef[$typesDef[$dependency]->getType()]->getName())
                            ->setParameter('{{ dependencyType }}', $typesDef[$dependency]->getName())
                            ->setParameter('{{ type }}', $typesDef[$type]->getName())
                            ->addViolation();
                    }
                }
            }
        }
    }
}
