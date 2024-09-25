<?php

namespace App\Controller\Crud;

use App\Entity\User;
use App\Security\EmailVerifier;
use App\Security\Roles;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        public UserPasswordHasherInterface $userPasswordHasher,
        private EmailVerifier $emailVerifier,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->setPermission(Action::BATCH_DELETE, 'USER_DELETE')
            ->setPermission(Action::DELETE, 'USER_DELETE')
            ->setPermission(Action::DETAIL, 'USER_DETAIL')
            ->setPermission(Action::INDEX, 'USER_INDEX')
            ->setPermission(Action::EDIT, 'USER_EDIT')
            ->setPermission(Action::NEW, 'USER_NEW')
        ;
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'];

        if ('saveAndReturn' === $submitButtonName) {
            $url = $this->container->get(AdminUrlGenerator::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                ->generateUrl();

            return $this->redirect($url);
        }

        return parent::getRedirectResponseAfterSave($context, $action);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('username')
                ->setFormTypeOptions([
                    'attr' => [
                        'autocomplete' => 'off',
                    ],
                ]),
            EmailField::new('email'),
            AssociationField::new('groups')
                ->setSortProperty('name')
                ->setFormTypeOption('by_reference', false)
                ->hideOnDetail()
                ->hideOnIndex()
                ->setPermission('ROLE_ADMIN'),
            ArrayField::new('groups')
                ->setPermission('ROLE_ADMIN')
                ->hideOnForm(),
            BooleanField::new('isVerified')
                ->setPermission('ROLE_ADMIN')
                ->hideOnIndex(),
        ];
        if ('new' !== $this->getContext()->getCrud()->getCurrentAction()) {
            $fields[1]->setDisabled();
        }

        $roles = ChoiceField::new('roles')
            ->setCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES, true)
            ->setCustomOption(ChoiceField::OPTION_CHOICES, Roles::getRoles())
            ->setRequired(false)
            ->setPermission('ROLE_ADMIN');

        $fields[] = $roles;

        $password = TextField::new('password')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => '(Repeat)',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'mapped' => false,
            ])
            ->setRequired(Crud::PAGE_NEW === $pageName)
            ->onlyOnForms()
        ;
        $fields[] = $password;

        return $fields;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        return $this->addPasswordEventListener($formBuilder);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        return $this->addPasswordEventListener($formBuilder);
    }

    private function addPasswordEventListener(FormBuilderInterface $formBuilder): FormBuilderInterface
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, $this->hashPassword());
    }

    private function hashPassword()
    {
        return function ($event) {
            $form = $event->getForm();
            if (!$form->isValid()) {
                return;
            }
            $password = $form->get('password')->getData();
            if (null === $password) {
                return;
            }

            $hash = $this->userPasswordHasher->hashPassword($this->getUser(), $password);
            $form->getData()->setPassword($hash);
        };
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $uow = $entityManager->getUnitOfWork();
        $uow->computeChangeSets();

        $changeset = $uow->getEntityChangeSet($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
        if (!empty($changeset['email'])) {
            $entityInstance->setVerified(false);
            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $entityInstance,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@drupguard.com', 'Drupguard'))
                    ->to($entityInstance->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
        }
    }
}
