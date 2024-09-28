<?php

namespace App\Form\Plugin;

use App\Plugin\PluginInfo;
use App\Plugin\Service\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class PluginAbstract extends AbstractType implements PluginInterface
{
    protected Manager $pluginManager;
    protected PluginInfo $pluginInfo;

    public function __construct(Manager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
        $this->pluginInfo = $this->pluginManager->getRelatedObject(get_class($this));
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'placeholder' => 'Choose an option',
                'required' => true,
                'choices' => $this->pluginInfo->getChoices(),
                'row_attr' => [
                    'class' => $this->pluginInfo->getId().'-plugin-type',
                ],
            ])
        ;
        // $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        foreach ($this->pluginInfo->getTypes() as $type) {
            $builder
                ->add($type->getId(), $type->getFormClass(), [
                    'label' => $type->getName(),
                    'row_attr' => [
                        'class' => $type->getId().'-'.$this->pluginInfo->getId().'-settings '.$this->pluginInfo->getId().'-settings',
                    ],
                    'empty_data' => new ($type->getEntityClass())
                ])
            ;
        }
    }

    public function onPreSetData(FormEvent $event)
    {
        /**
         * @var $data \App\Entity\Plugin\PluginAbstract
         */
        $data = $event->getData();
        if ($data) {
            foreach ($this->pluginInfo->getTypes() as $type) {
                if ($type->getId() === $data->getType()) {
                    continue;
                }
                $data->{'set'.ucfirst($type->getId())}(null);
            }
            $event->setData($data);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->pluginInfo->getEntityClass(),
            'error_bubbling' => false,
        ]);
    }
}
