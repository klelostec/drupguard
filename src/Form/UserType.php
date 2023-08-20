<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        switch ($options['mode']) {
            case 'edit':
                $builder
                    ->add('email')
                    ->add('oldPlainPassword', PasswordType::class, [
                        'mapped' => false,
                        'label' => 'Old password',
                        'constraints' => [
                            new UserPassword()
                        ]
                    ])
                    ->add('plainPassword', RepeatedType::class, [
                        'mapped' => false,
                        'type' => PasswordType::class,
                        'invalid_message' => 'The password fields must match.',
                        'options' => ['attr' => ['class' => 'password-field']],
                        'required' => false,
                        'first_options'  => [
                            'label' => 'New password',
                            'constraints' => [
                                new Length([
                                    'min' => 6,
                                    'minMessage' => 'Your password should be at least {{ limit }} characters',
                                    // max length allowed by Symfony for security reasons
                                    'max' => 4096,
                                ]),
                            ],
                        ],
                        'second_options' => [
                            'label' => 'Repeat new password'
                        ],
                    ])
                ;
                break;
            case 'registration':
                $builder
                    ->add('username')
                    ->add('email')
                    ->add('plainPassword', RepeatedType::class, [
                        'mapped' => false,
                        'type' => PasswordType::class,
                        'invalid_message' => 'The password fields must match.',
                        'options' => ['attr' => ['class' => 'password-field']],
                        'required' => true,
                        'first_options'  => [
                            'label' => 'Password',
                            'constraints' => [
                                new Length([
                                    'min' => 6,
                                    'minMessage' => 'Your password should be at least {{ limit }} characters',
                                    // max length allowed by Symfony for security reasons
                                    'max' => 4096,
                                ]),
                            ],
                        ],
                        'second_options' => [
                            'label' => 'Repeat password'
                        ],
                    ])
                    ->add('agreeTerms', CheckboxType::class, [
                        'mapped' => false,
                        'constraints' => [
                            new IsTrue([
                                'message' => 'You should agree to our terms.',
                            ]),
                        ],
                    ]);
                break;
            case 'admin_create':
            case 'admin_edit':
                $builder
                    ->add('username')
                    ->add('email')
                    ->add('plainPassword', RepeatedType::class, [
                        'mapped' => false,
                        'type' => PasswordType::class,
                        'invalid_message' => 'The password fields must match.',
                        'options' => ['attr' => ['class' => 'password-field']],
                        'required' => $options['mode'] === 'admin_create',
                        'first_options'  => [
                            'label' => $options['mode'] === 'admin_create' ? 'Password' : 'New password',
                            'constraints' => [
                                new Length([
                                    'min' => 6,
                                    'minMessage' => 'The password should be at least {{ limit }} characters',
                                    // max length allowed by Symfony for security reasons
                                    'max' => 4096,
                                ]),
                            ],
                        ],
                        'second_options' => [
                            'label' => $options['mode'] === 'admin_create' ? 'Repeat password' : 'Repeat new password'
                        ],
                    ])
                    ->add('isVerified')
                    ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
                        $user = $event->getData();
                        $form = $event->getForm();

                        $form->add('isAdmin', CheckboxType::class, [
                            'mapped' => false,
                            'required' => false,
                            'data' => $user && $user->hasRole('ROLE_ADMIN')
                        ]);
                    });
                break;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => User::class,
                'mode' => 'registration'
            ])
            ->addAllowedTypes('mode', 'string')
            ->addAllowedValues('mode', ['registration', 'admin_create', 'edit', 'admin_edit'])
        ;

    }
}
