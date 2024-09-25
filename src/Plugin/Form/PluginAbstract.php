<?php

namespace App\Plugin\Form;

use App\Plugin\Manager;
use App\Plugin\PluginInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class PluginAbstract extends AbstractType implements PluginInterface
{

    protected Manager $pluginManager;
    protected PluginInfo $pluginInfo;

    public function __construct(Manager $pluginManager) {
        $this->pluginManager = $pluginManager;
        $reflection = new \ReflectionClass($this);
        $this->pluginInfo = $this->pluginManager->getPluginInfo(mb_strtolower($reflection->getShortName()));
    }

    public function getPluginManager(): Manager {
        return $this->pluginManager;
    }

    public function getPluginInfo(): PluginInfo {
        return $this->pluginInfo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => $this->pluginInfo->getChoices(),
                'row_attr' => [
                    'class' => $this->pluginInfo->getId() . '-plugin-type',
                ]
            ])
        ;
        //$builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        foreach ($this->pluginInfo->getTypes() as $type) {
            $builder
                ->add($type->getId(), $type->getForm(), [
                    'row_attr' => [
                        'class' => $type->getId() . '-' . $this->pluginInfo->getId() . '-settings ' . $this->pluginInfo->getId() . '-settings',
                    ]
                ])
            ;
        }
    }

    public function onPreSetData(FormEvent $event)
    {
        /**
         * @var $data \App\Plugin\Entity\PluginAbstract
         */
        $data = $event->getData();
        if ($data) {
            foreach ($this->pluginInfo->getTypes() as $type) {
                if ($type->getId() === $data->getType()) {
                    continue;
                }
                $data->{'set' . ucfirst($type->getId())}(null);
            }
            $event->setData($data);
        }
    }

    public function configureOptions(OptionsResolver $resolver) :void
    {
        $resolver->setDefaults([
            'data_class' => $this->pluginInfo->getEntity(),
            'error_bubbling' => false,
        ]);
    }
}
