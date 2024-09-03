<?php

namespace App\Controller\Crud;

use App\Entity\Group;
use App\Security\Roles;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class GroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Group::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            AssociationField::new('projects')
                ->setSortProperty('name')
                ->setFormTypeOption('by_reference', false)
                ->hideOnDetail()
                ->hideOnIndex(),
        ];

        $roles = ChoiceField::new('roles')
            ->setCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES, TRUE)
            ->setCustomOption(ChoiceField::OPTION_CHOICES, Roles::getRoles())
            ->setRequired(false);
        ;
        $fields[] = $roles;

        return $fields;
    }
}
