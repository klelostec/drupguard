<?php

namespace App\Controller\Crud;

use App\EasyAdmin\Field\MachineNameField;
use App\Entity\Project;
use App\Plugin\Entity\PluginAbstract;
use App\Plugin\Form\Build;
use App\Plugin\Form\Source;
use App\Plugin\Manager;
use App\Security\Roles;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
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
            ->addAssetMapperEntry('plugin_settings')
        ;
    }

    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->setPermission(Action::BATCH_DELETE, 'PROJECT_DELETE')
            ->setPermission(Action::DELETE, 'PROJECT_DELETE')
            ->setPermission(Action::DETAIL, 'PROJECT_DETAIL')
            ->setPermission(Action::EDIT, 'PROJECT_EDIT')
            ->setPermission(Action::INDEX, 'PROJECT_INDEX')
            ->setPermission(Action::NEW, 'PROJECT_NEW')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('General'),
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            MachineNameField::new('machine_name')
                ->setFormTypeOption('source_field', 'name')
                ->hideWhenUpdating(),
            CollectionField::new('projectMembers')
                ->useEntryCrudForm(ProjectMemberCrudController::class)
                ->setEntryIsComplex()
                ->hideOnIndex()
                ->hideWhenCreating(),
            BooleanField::new('isPublic')
                ->hideOnIndex(),
            BooleanField::new('isPublic')
                ->renderAsSwitch(false)
                ->hideOnForm(),
            FormField::addTab('Plugins'),
            CollectionField::new('sourcePlugins', 'Source')
                ->setFormTypeOption('error_bubbling', false)
                ->setEntryType(Source::class)
                ->setEntryIsComplex()
                ->renderExpanded()
                ->addCssClass('source-plugins')
                ->hideOnIndex(),
            CollectionField::new('buildPlugins', 'Build')
                ->setFormTypeOption('error_bubbling', false)
                ->setEntryType(Build::class)
                ->setEntryIsComplex()
                ->renderExpanded()
                ->addCssClass('build-plugins')
                ->hideOnIndex(),
        ];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        if (!$this->isGranted(Roles::ADMIN)) {
            $queryBuilder
                ->leftJoin('entity.projectMembers', 'pm')
                ->leftJoin('pm.user', 'pmu')
                ->leftJoin('pm.groups', 'pmg')
                ->leftJoin('pmg.users', 'pmgu')
                ->groupBy('entity.id')
                ->where('entity.isPublic = 1 OR pmu.id = :userId OR pmgu.id = :userId')
                ->setParameter('userId', $this->getUser()->getId());
        }

        return $queryBuilder;
    }

    protected function setProjectToProjectMembers($entityInstance): void {
        foreach ($entityInstance->getProjectMembers() as $projectMember) {
            if ($projectMember->getProject()) {
                continue;
            }
            $projectMember->setProject($entityInstance);
        }
    }

    protected function removeUselessPluginType($entityInstance): void {
        $manager = $this->container->get(Manager::class);
        foreach ($manager->getPluginInfos() as $pluginInfo) {
            /**
             * @var Collection<int, PluginAbstract> $pluginCollection
             */
            $pluginCollection = $entityInstance->{'get' . mb_ucfirst($pluginInfo->getId()) . 'Plugins'}();
            foreach ($pluginCollection as $plugin) {
                $currentType = $plugin->getType();
                foreach ($pluginInfo->getTypes() as $typeInfo) {
                    if ($currentType === $typeInfo->getId()) {
                        continue;
                    }
                    $plugin->{'set' . mb_ucfirst($typeInfo->getId())}(null);
                }
            }
        }
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
        $this->removeUselessPluginType($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->setProjectToProjectMembers($entityInstance);
        $this->removeUselessPluginType($entityInstance);
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


    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
           Manager::class => '?' . Manager::class,
        ]);
    }
}
