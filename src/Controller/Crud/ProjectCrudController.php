<?php

namespace App\Controller\Crud;

use App\EasyAdmin\Field\MachineNameField;
use App\Entity\Project;
use App\Entity\ProjectMember;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProjectCrudController extends AbstractCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('form/types/custom.html.twig')
        ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return parent::configureAssets($assets)
            ->addAssetMapperEntry('machine_name')
        ;
    }

    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            MachineNameField::new('machine_name')
                ->setFormTypeOption('source_field', 'name')
                ->hideWhenUpdating(),
            CollectionField::new('projectMembers')
                ->useEntryCrudForm(ProjectMemberCrudController::class)
                ->setEntryIsComplex()
                //->renderExpanded()
                ->hideOnIndex()
                ->hideWhenCreating(),
            BooleanField::new('isPublic'),
        ];
    }

    protected function setProjectToProjectMembers($entityInstance): void {
        foreach ($entityInstance->getProjectMembers() as $projectMember) {
            if ($projectMember->getProject()) {
                continue;
            }
            $projectMember->setProject($entityInstance);
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->setProjectToProjectMembers($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->setProjectToProjectMembers($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /**
         * @var $entityInstance Project
         */
        foreach ($entityInstance->getProjectMembers() as $projectMember) {
            $entityManager->detach($projectMember);
        }
        parent::deleteEntity($entityManager, $entityInstance);
    }
}
