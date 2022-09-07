<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProfileTokenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tokenApi', TextareaType::class, [
                'empty_data' => '',
                'disabled' => TRUE,
            ])
            ->add('generate', SubmitType::class)
            ->add('revoke', SubmitType::class, [
                'attr' => ['class' => 'btn-danger'],
            ])
        ;
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                /** @var User|null $data */
                $data = $event->getData();
                $form = $event->getForm();
                if (!$data) {
                    $form->remove('generate');
                    $form->remove('revoke');
                }
                else if(!empty($data->getTokenApi())) {
                    $form->remove('generate');
                }
                else {
                    $form->remove('revoke');
                }
            }
        );

    }
}
