<?php

namespace App\Plugin\Form\Type;

use App\Plugin\Manager;
use App\Plugin\TypeInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class TypeAbstract extends AbstractType
{
    private Manager $pluginManager;
    private TypeInfo $typeInfo;

    public function __construct(Manager $pluginManager) {
        $this->pluginManager = $pluginManager;
        preg_match('#App\\\\Plugin\\\\Form\\\\Type\\\\([a-z]+)\\\\([a-z]+)#i', get_class($this), $matches);
        $this->typeInfo = $this->pluginManager->getTypeInfo(mb_strtolower($matches[1]), mb_strtolower($matches[2]));
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reflection = new \ReflectionClass($this->typeInfo->getEntity());
        foreach ($reflection->getProperties() as $property) {
            if ($property->getName() === 'id') {
                continue;
            }
            $getter = 'get' . ucfirst($property->getName());
            if (
                !$reflection->hasMethod($getter) ||
                !$reflection->getMethod($getter)->isPublic()
            ) {
                continue;
            }

            $name = $property->getName();
            $type = null;
            $options = [
                'required' => true
            ];
            $this->alterPropertyFormType($name, $type, $options);
            $builder
                ->add($name, $type, $options);
        }
    }

    public function alterPropertyFormType(string $property, ?string &$type, array &$typeOptions) :void {
        return;
    }

    public function configureOptions(OptionsResolver $resolver) :void
    {
        $resolver->setDefaults([
            'data_class' => $this->typeInfo->getEntity(),
            'error_bubbling' => false,
        ]);
    }
}
