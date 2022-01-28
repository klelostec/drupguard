<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\User;
use App\Form\Type\AutocompleteType;
use App\Service\GitHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProjectType extends AbstractType
{
    private $generator;

    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Project|null $project */
        $project = $options['data'] ?? null;
        $showEmail = $project && $project->needEmail() ? true : false;
        $showCron = $project && $project->hasCron() ? true : false;
        $showUser = !$project || !$project->isPublic() ? true : false;
        $builder
            ->add('name', null, [
                'help' => $project && $project->getId() ? 'Machine name : ' . $project->getMachineName() : '',
                'attr' => [
                    'data-ajax-machine-name' => $this->generator->generate('project_ajax_machine_name'),
                ],
            ])
            ->add('gitRemoteRepository', null, [
                'attr' => [
                    'data-ajax-git-branches' => $this->generator->generate('project_ajax_git_branches'),
                    'class' => 'js-git-remote-repository'
                ],
            ])
            ->add('composerVersion', ChoiceType::class, [
                'choices' => Project::COMPOSER_VERSION,
                'row_attr' => ['class' => 'mb-3 row js-composer-version-row'],
            ])
            ->add('drupalDirectory', null, [
                'help' => 'For Drupal 8, relates to directory which contain composer.json and composer.lock files. For Drupal 7, relates to directory which contain index.php file. If Drupal directory is located at the root, no need to fill this field.',
            ])
            ->add('needEmail')
            ->add('emailLevel', ChoiceType::class, [
                'choices' => Project::EMAIL_LEVEL,
                'row_attr' => ['class' => 'mb-3 row js-email-level-row' . (!$showEmail ? ' d-none' : '')],
            ])
            ->add('emailExtra', null, [
                'row_attr' => ['class' => 'mb-3 row js-email-extra-row' . (!$showEmail ? ' d-none' : '')],
                'help' => 'By default, email are sent to allowed users. If you need extra users email, fill this field with emails, one per line.',
            ])
            ->add('hasCron')
            ->add('cronFrequency', null, [
                'row_attr' => ['class' => 'mb-3 row js-cron-frequency-row' . (!$showCron ? ' d-none' : '')],
            ])
            ->add('isPublic')
            ->add('allowedUsers', AutocompleteType::class, [
                'row_attr' => ['class' => 'mb-3 row js-allowed-users-row' . (!$showUser ? ' d-none' : '')],
                'multiple' => true,
                'class' => User::class,
                'url' => '/autocomplete/user'
            ])
            ->add('ignored_modules', null, [
                'help' => 'Ignored modules will not be taken into account to calculate project status. If you need to ignore some modules, fill this field with modules machine name, one per line.',
            ])
            ->add('submit', SubmitType::class)
        ;

        if (!$project){
            $builder->add('machineName', null, [
                'row_attr' => ['class' => 'mb-3 row js-machine-name-row d-none'],
            ]);
        }

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                /** @var Project|null $data */
                $data = $event->getData();
                if (!$data) {
                    return;
                }
                $this->setupGitBranchField(
                    $event->getForm(),
                    $data->getGitRemoteRepository(),
                    $data->getGitBranch()
                );
            }
        );

        $builder->get('gitRemoteRepository')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $this->setupGitBranchField(
                    $form->getParent(),
                    $form->getData(),
                    NULL
                );
            }
        );
    }

    private function setupGitBranchField(FormInterface $form, ?string $repo, ?string $branch)
    {
        if (null === $repo) {
            $form->remove('gitBranch');
            return;
        }
        $choices = GitHelper::getRemoteBranchesWithoutCheckout($repo);
        if (empty($choices)) {
            $form->remove('gitBranch');
            return;
        }
        $default = $branch && isset($choices[$branch]) ? $branch : GitHelper::getRemoteDefaultBrancheWithoutCheckout($repo);
        $form->add('gitBranch', ChoiceType::class, [
            'choices' => $choices,
            'data' => $default
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'validation_groups' => function (FormInterface $form) {
                $groups = array('Default');
                /**
                 * @var Project $data
                 */
                $data = $form->getData();

                if ($data->hasCron()) {
                    $groups[] = 'cron';
                } else {
                    $groups[] = 'not_cron';
                }

                if ($data->needEmail()) {
                    $groups[] = 'email';
                } else {
                    $groups[] = 'not_email';
                }

                if ($data->isPublic()) {
                    $groups[] = 'public';
                } else {
                    $groups[] = 'not_public';
                }

                if (!$data->getId()) {
                    $groups[] = 'machine_name';
                }

                return $groups;
            }
        ]);
    }
}
