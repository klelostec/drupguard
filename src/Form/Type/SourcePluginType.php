<?php

namespace App\Form\Type;

use App\Entity\SourcePlugin;
use App\Service\SourcePluginManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SourcePluginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => SourcePluginManager::getTypes()
            ])
            ->add('localSourceSettings', LocalSourceSettingsType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SourcePlugin::class,
        ]);
    }
}