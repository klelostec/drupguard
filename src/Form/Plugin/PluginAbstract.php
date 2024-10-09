<?php

namespace App\Form\Plugin;

use App\Plugin\PluginInfo;
use App\Plugin\Service\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\String\u;

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
                    'class' => u($this->pluginInfo->getId())->snake().'-plugin-type',
                ],
            ])
        ;
        // $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        foreach ($this->pluginInfo->getTypes() as $type) {
            $builder
                ->add(u($type->getId())->camel(), $type->getFormClass(), [
                    'label' => $type->getName(),
                    'row_attr' => [
                        'class' => u($type->getId())->snake().'-'.u($this->pluginInfo->getId())->snake().'-settings '.u($this->pluginInfo->getId())->snake().'-settings',
                    ],
                    'empty_data' => new ($type->getEntityClass()),
                    'help' => $type->getHelp()
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
                $data->{'set'.mb_ucfirst(u($type->getId())->camel())}(null);
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
