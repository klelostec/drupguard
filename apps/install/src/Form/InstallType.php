<?php

namespace Install\Form;

use Install\Entity\Install;
use Install\Form\Type\DbDriverType;
use Install\Form\Type\InstallEmailType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstallType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            switch ($form->getConfig()->getOption('step')) {
                case 'requirements':
                    $form->add('requirement_php_binary', TextType::class, [
                        'label' => 'PHP binary path',
                    ]);
                    $form->add('requirement_composer_v1_binary', TextType::class, [
                        'label' => 'Composer v1 binary path',
                    ]);
                    $form->add('requirement_composer_v2_binary', TextType::class, [
                        'label' => 'Composer v2 binary path',
                    ]);
                    $form->add('next', SubmitType::class);
                    break;
                case 'database':
                    $form->add('db_driver', DbDriverType::class, [
                        'label' => 'Type',
                    ]);
                    $form->add('db_host', TextType::class, [
                        'label' => 'Host',
                    ]);
                    $form->add('db_user', TextType::class, [
                        'label' => 'User',
                    ]);
                    $form->add('db_password', PasswordType::class, [
                        'label' => 'Password',
                    ]);
                    $form->add('db_database', TextType::class, [
                        'label' => 'Database',
                    ]);
                    $form->add('db_parameters', TextType::class, [
                        'label' => 'Parameters',
                        'required' => FALSE
                    ]);
                    $form->add('next', SubmitType::class);
                    $form->add('previous', SubmitType::class, [
                        'attr' => ['class' => 'btn-secondary', 'formnovalidate' => 'formnovalidate']
                    ]);
                    break;
                case 'email':
                    $form->add('email_type_install', InstallEmailType::class, [
                        'label' => 'Type',
                        'required' => FALSE
                    ]);
                    $form->add('email_dsn_custom', TextType::class, [
                        'label' => 'DSN',
                        'required' => FALSE
                    ]);
                    $form->add('email_command', TextType::class, [
                        'label' => 'Command',
                        'required' => FALSE
                    ]);
                    $form->add('email_host', TextType::class, [
                        'label' => 'Host',
                        'required' => FALSE
                    ]);
                    $form->add('email_user', TextType::class, [
                        'label' => 'User',
                        'required' => FALSE
                    ]);
                    $form->add('email_password', TextType::class, [
                        'label' => 'Password',
                        'required' => FALSE
                    ]);
                    $form->add('email_local_domain', TextType::class, [
                        'label' => 'The domain name to use in HELO command',
                        'required' => FALSE
                    ]);
                    $form->add('email_restart_threshold', IntegerType::class, [
                        'label' => 'The maximum number of messages to send before re-starting the transport',
                        'required' => FALSE
                    ]);
                    $form->add('email_restart_threshold_sleep', IntegerType::class, [
                        'label' => 'The number of seconds to sleep between stopping and re-starting the transport',
                        'required' => FALSE
                    ]);
                    $form->add('email_ping_threshold', IntegerType::class, [
                        'label' => 'The minimum number of seconds between two messages required to ping the server',
                        'required' => FALSE
                    ]);
                    $form->add('email_max_per_second', IntegerType::class, [
                        'label' => 'The number of messages to send per second (0 to disable this limitation)',
                        'required' => FALSE
                    ]);
                    $form->add('email', EmailType::class, [
                        'label' => 'Email',
                        'required' => FALSE
                    ]);
                    $form->add('check_email', SubmitType::class, [
                        'label' => 'Check email'
                    ]);

                    $form->add('next', SubmitType::class);
                    $form->add('previous', SubmitType::class, [
                        'attr' => ['class' => 'btn-secondary', 'formnovalidate' => 'formnovalidate']
                    ]);
                    break;
                case 'confirm':
                    $form->add('process', SubmitType::class);
                    $form->add('previous', SubmitType::class, [
                        'attr' => ['class' => 'btn-secondary', 'formnovalidate' => 'formnovalidate']
                    ]);
                    break;
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $steps = self::getSteps();
        $resolver->setDefaults([
            'data_class' => Install::class,
            'translation_domain' => 'install',
            'step' => $steps[0],
            'validation_groups' => [$steps[0]]
        ]);
        $resolver->setAllowedTypes('step', 'string');
        $resolver->setAllowedValues('step', $steps);
    }

    public static function getSteps(): array {
        return ['requirements', 'database', 'email', 'confirm'];
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix() : string {
        return 'install';
    }

}
