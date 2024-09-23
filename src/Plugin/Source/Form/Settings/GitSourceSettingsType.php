<?php

namespace App\Plugin\Source\Form\Settings;

use App\Plugin\Source\Entity\Settings\GitSourceSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GitSourceSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('repository', TextType::class, [
                'required' => true
            ])
            ->add('branch', TextType::class, [
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GitSourceSettings::class,
        ]);
    }
}