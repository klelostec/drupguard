<?php

namespace App\Form;

use App\Entity\Project;
use App\Service\GitHelper;
use App\Validator\Constraints\CronExpression;
use App\Validator\Constraints\GitRemote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
            ])
            ->add('hasCron')
            ->add('cronFrequency', TextType::class, $cronFreqOption)
            ->add('isPublic')
            ->add('allowedUsers')
        ;

        $formModifierHasCron = function (FormInterface $form, $hasCron = false) use ($cronFreqOption) {
            if(!$hasCron) {
                $cronFreqOption['row_attr']['class'] .=' d-none';
            }
            $cronFreqOption['required'] = boolval($hasCron);

            $form->add('cronFrequency', TextType::class, $cronFreqOption);
        };

        $formModifierGitBranch = function (FormInterface $form, $gitRemote = '') {
            $choices = [];
            if(!empty($gitRemote)) {
                $choices = GitHelper::getRemoteBranchesWithoutCheckout($gitRemote);
            }

            $form->add('gitBranch', ChoiceType::class, [
              'choices' => $choices
            ]);
        };

        $builder->addEventListener(
          FormEvents::POST_SET_DATA,
          function (FormEvent $event) use ($formModifierHasCron, $formModifierGitBranch) {
              $data = $event->getData();
              $form = $event->getForm();
              $formModifierHasCron($form, $data->hasCron());
              $formModifierGitBranch($form, $data->getGitRemoteRepository());

              if($data->getId()) {
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
                }
                else {
                    $groups[] = 'NotCron';
                }

                return $groups;
            }
        ]);
    }
}
