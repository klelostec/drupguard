<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class InstallFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Article title',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Article content',
            ])
//            ->add('resume', TextareaType::class, [
//                'label' => 'Article resume',
//            ])
            ->add('publishDate', DateTimeType::class, [
                'label' => 'Publish on',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
            ]);
    }
}