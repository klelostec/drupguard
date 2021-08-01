<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User|null $user */
        $user = $options['data'] ?? null;
        $builder
            ->add('username')
            ->add('firstname')
            ->add('lastname')
            ->add('email', EmailType::class)
            ->add('plainPassword', RepeatedType::class, [
                'required' => !$user || !$user->getId(),
                'type' => PasswordType::class,
                'first_options'  => [
                    'label' => 'Password',
                ],
                'second_options' => [
                    'label' => 'Repeat password'
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'multiple' => true,
                'expanded' => true, // render check-boxes
                'choices' => [
                    'admin' => 'ROLE_ADMIN'
                ]
            ])
            ->add('isVerified')
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => function (FormInterface $form) {
                $groups = array('Default', 'user_admin');
                /**
                 * @var User $data
                 */
                $data = $form->getData();

                if (!$data->getId()) {
                    $groups[] = 'user_admin_add';
                }

                return $groups;
            }
        ]);
    }
}
