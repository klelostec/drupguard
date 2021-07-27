<?php

namespace App\Form;

use App\Entity\Analyse;
use App\Entity\Project;
use App\Service\GitHelper;
use App\Validator\Constraints\CronExpression;
use App\Validator\Constraints\GitRemote;
use App\Validator\Constraints\MultipleEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $emailLevelOption = [
          'required' => false,
          'row_attr' => ['class' => 'needEmail-group emailLevel-group'],
          'choices' => [
            'Error' => Analyse::ERROR,
            'Warning' => Analyse::WARNING,
            'Success' => Analyse::SUCCESS,
          ]
        ];
        $emailExtraOption = [
          'required' => false,
          'row_attr' => ['class' => 'needEmail-group'],
          'constraints' => [
            new MultipleEmail(),
          ],
          'help' => 'By default, email are sent to allowed users. If you need extra users email, fill this field with emails, one per line.',
        ];
        $cronFreqOption = [
          'required' => false,
          'constraints' => [
            new CronExpression([
              'groups' => array('Cron'),
            ]),
            new Blank([
              'groups' => array('NotCron'),
            ]),
          ],
          'row_attr' => ['class' => 'hasCron-group']
        ];

        $builder
            ->add('name', TextType::class, [
              'constraints' => [
                new NotBlank()
              ]
            ])
            ->add('machineName', TextType::class, [
              'required' => true,
            ])
            ->add('gitRemoteRepository', TextType::class, [
              'constraints' => [
                new GitRemote()
              ]
            ])
            ->add('gitBranch', ChoiceType::class, [
              'required' => true,
            ])
            ->add('drupalDirectory', TextType::class, [
              'required' => false,
              'constraints' => [
                new Regex('/^(\/[\w-]+)*$/i')
              ]
            ])
            ->add('needEmail')
            ->add('emailLevel', ChoiceType::class, $emailLevelOption)
            ->add('emailExtra', TextareaType::class, $emailExtraOption)
            ->add('hasCron')
            ->add('cronFrequency', TextType::class, $cronFreqOption)
            ->add('isPublic')
            ->add('allowedUsers')
            ->add('ignoredModules', TextareaType::class, [
                'required' => false,
                'help' => 'Ignored modules will not be taken into account to calculate project status. If you need to ignore some modules, fill this field with modules machine name, one per line.',
            ])
        ;

        $formModifierNeedEmail = function (FormInterface $form, $needEmail = false) use ($emailLevelOption, $emailExtraOption) {
            if (!$needEmail) {
                $emailLevelOption['row_attr']['class'] .=' d-none';
                $emailExtraOption['row_attr']['class'] .=' d-none';
            }
            $emailLevelOption['required'] = boolval($needEmail);

            $form->add('emailLevel', ChoiceType::class, $emailLevelOption);
            $form->add('emailExtra', TextareaType::class, $emailExtraOption);
        };

        $formModifierHasCron = function (FormInterface $form, $hasCron = false) use ($cronFreqOption) {
            if (!$hasCron) {
                $cronFreqOption['row_attr']['class'] .=' d-none';
            }
            $cronFreqOption['required'] = boolval($hasCron);

            $form->add('cronFrequency', TextType::class, $cronFreqOption);
        };

        $formModifierGitBranch = function (FormInterface $form, $gitRemote = '') {
            $choices = [];
            if (!empty($gitRemote)) {
                $choices = GitHelper::getRemoteBranchesWithoutCheckout($gitRemote);
            }

            $form->add('gitBranch', ChoiceType::class, [
              'choices' => $choices
            ]);
        };

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) use ($formModifierHasCron, $formModifierGitBranch, $formModifierNeedEmail) {
              $data = $event->getData();
              $form = $event->getForm();
              $formModifierNeedEmail($form, $data->needEmail());
              $formModifierHasCron($form, $data->hasCron());
              $formModifierGitBranch($form, $data->getGitRemoteRepository());

              if ($data->getId()) {
                  $form->add('name', TextType::class, [
                    'help' => 'Machine name : ' . $data->getMachineName(),
                    'constraints' => [
                      new NotBlank()
                    ]
                  ]);
                  $form->remove('machineName');
              }
          }
        );
        $builder->get('needEmail')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifierNeedEmail) {
              $needEmail = $event->getForm()->getData();
              $formModifierNeedEmail($event->getForm()->getParent(), $needEmail);
          }
        );
        $builder->get('hasCron')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifierHasCron) {
              $hasCron = $event->getForm()->getData();
              $formModifierHasCron($event->getForm()->getParent(), $hasCron);
          }
        );
        $builder->get('gitRemoteRepository')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifierGitBranch) {
              $gitRemoteRepository = $event->getForm()->getData();
              $formModifierGitBranch($event->getForm()->getParent(), $gitRemoteRepository);
          }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'validation_groups' => function (FormInterface $form) {
                $groups = array('Default');
                $data = $form->getData();

                if ($data->hasCron()) { // then we want password to be required
                    $groups[] = 'Cron';
                } else {
                    $groups[] = 'NotCron';
                }

                return $groups;
            }
        ]);
    }
}
