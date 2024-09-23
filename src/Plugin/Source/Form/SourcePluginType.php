<?php

namespace App\Plugin\Source\Form;

use App\Plugin\Source\Entity\SourcePlugin;
use App\Plugin\Source\Form\Settings\GitSourceSettingsType;
use App\Plugin\Source\Form\Settings\LocalSourceSettingsType;
use App\Plugin\Source\SourcePluginManager;
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
                'choices' => SourcePluginManager::getTypes(),
                'row_attr' => [
                    'class' => 'source-plugin-type',
                ]
            ])
            ->add('localSourceSettings', LocalSourceSettingsType::class, [
                'row_attr' => [
                    'class' => 'local-source-settings source-settings',
                ]
            ])
            ->add('gitSourceSettings', GitSourceSettingsType::class, [
                'row_attr' => [
                    'class' => 'git-source-settings source-settings',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SourcePlugin::class,
        ]);
    }
}