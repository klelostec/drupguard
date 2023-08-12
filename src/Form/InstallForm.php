<?php

namespace App\Form;

use App\Entity\Install;
use App\Form\Type\DbDriverType;
use App\Form\Type\InstallEmailType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstallForm extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        switch ($options['flow_step']) {
            case 1:
                $builder->add('requirement_php_binary', TextType::class, [
                    'label' => 'PHP binary path',
                ]);
                $builder->add('requirement_composer_v1_binary', TextType::class, [
                    'label' => 'Composer v1 binary path',
                ]);
                $builder->add('requirement_composer_v2_binary', TextType::class, [
                    'label' => 'Composer v2 binary path',
                ]);
                break;
            case 2:
                $builder->add('db_driver', DbDriverType::class, [
                    'label' => 'Type',
                ]);
                $builder->add('db_host', TextType::class, [
                    'label' => 'Host',
                ]);
                $builder->add('db_user', TextType::class, [
                    'label' => 'User',
                ]);
                $builder->add('db_password', PasswordType::class, [
                    'label' => 'Password',
                ]);
                $builder->add('db_database', TextType::class, [
                    'label' => 'Database',
                ]);
                break;
            case 3:
                $builder->add('email_type_install', InstallEmailType::class, [
                    'label' => 'Type',
                ]);
                $builder->add('email_dsn_custom', TextType::class, [
                    'label' => 'DSN',
                    'required' => FALSE
                ]);
                $builder->add('email_command', TextType::class, [
                    'label' => 'Command',
                    'required' => FALSE
                ]);
                $builder->add('email_host', TextType::class, [
                    'label' => 'Host',
                    'required' => FALSE
                ]);
                $builder->add('email_user', TextType::class, [
                    'label' => 'User',
                    'required' => FALSE
                ]);
                $builder->add('email_password', TextType::class, [
                    'label' => 'Password',
                    'required' => FALSE
                ]);
                $builder->add('email_local_domain', TextType::class, [
                    'label' => 'Local domain',
                    'required' => FALSE
                ]);
                $builder->add('email_restart_threshold', IntegerType::class, [
                    'label' => 'Restart threshold',
                    'required' => FALSE
                ]);
                $builder->add('email_restart_threshold_sleep', IntegerType::class, [
                    'label' => 'Restart threshold sleep',
                    'required' => FALSE
                ]);
                $builder->add('email_ping_threshold', IntegerType::class, [
                    'label' => 'Ping threshold',
                    'required' => FALSE
                ]);
                $builder->add('email_max_per_second', IntegerType::class, [
                    'label' => 'Max per second',
                    'required' => FALSE
                ]);

                break;
            case 4:
                break;
            default:
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Install::class,
            'translation_domain' => 'form'
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix() : string {
        return 'install';
    }

}
