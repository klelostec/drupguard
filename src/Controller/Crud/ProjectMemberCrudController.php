<?php

namespace App\Controller\Crud;

use App\EasyAdmin\Field\MachineNameField;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Security\ProjectRoles;
use App\Security\Roles;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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
}
