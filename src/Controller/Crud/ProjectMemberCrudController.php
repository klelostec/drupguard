<?php

namespace App\Controller\Crud;

use App\Entity\ProjectMember;
use App\Security\ProjectRoles;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class ProjectMemberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectMember::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('project')
            ->add('user')
            ->add('groups')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [];
        if ($this->getContext()->getCrud()->getEntityFqcn() !== 'App\Entity\Project') {
            $fields[] = AssociationField::new('project');
        }
        $fields[] = AssociationField::new('user');
        $fields[] = AssociationField::new('groups');

        $roles = ChoiceField::new('role')
            ->setCustomOption(ChoiceField::OPTION_CHOICES, ProjectRoles::getRoles())
        ;
        $fields[] = $roles;

        return $fields;
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /**
         * @var ProjectMember $entityInstance
         */
        if ($entityInstance->getRole() === ProjectRoles::OWNER && !$entityInstance->getProject()->hasOwner($entityInstance)) {
            $this->addFlash('danger', 'Project needs at least one member with owner role.');
            $url = $this->container->get(AdminUrlGenerator::class)->generateUrl();

            $this->redirect($url);
            return;
        }
        parent::deleteEntity($entityManager, $entityInstance);
    }
}
