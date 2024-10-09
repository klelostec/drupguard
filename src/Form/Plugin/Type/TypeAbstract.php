<?php

namespace App\Form\Plugin\Type;

use App\Plugin\Service\Manager;
use App\Plugin\TypeInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\String\u;

abstract class TypeAbstract extends AbstractType
{
    private Manager $pluginManager;
    private TypeInfo $typeInfo;

    public function __construct(Manager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
        $this->typeInfo = $this->pluginManager->getRelatedObject(get_class($this));
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reflection = new \ReflectionClass($this->typeInfo->getEntityClass());
        foreach ($reflection->getProperties() as $property) {
            if ('id' === $property->getName()) {
                continue;
            }

            $getter = 'get'.mb_ucfirst(u($property->getName())->camel());
            if (
                !$reflection->hasMethod($getter)
                || !$reflection->getMethod($getter)->isPublic()
            ) {
                continue;
            }

            $name = $property->getName();
            $type = null;
            $options = [
                'required' => true,
            ];
            $this->alterPropertyFormType($name, $type, $options);
            $builder
                ->add($name, $type, $options);
        }
    }

    public function alterPropertyFormType(string $property, ?string &$type, array &$typeOptions): void
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->typeInfo->getEntityClass(),
            'error_bubbling' => false,
        ]);
    }
}
