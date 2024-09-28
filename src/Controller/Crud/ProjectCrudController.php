<?php

namespace App\Controller\Crud;

use App\EasyAdmin\Field\MachineNameField;
use App\Entity\Plugin\PluginAbstract;
use App\Entity\Project;
use App\Plugin\Service\Manager;
use App\Security\Roles;
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
use Symfony\Component\Validator\Constraints\Count;

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
        $fields = [
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
        ];

        $manager = $this->container->get(Manager::class);
        $reflection = new \ReflectionClass(Project::class);
        foreach ($manager->getPlugins() as $pluginInfo) {
            $property = $pluginInfo->getId() . 'Plugins';
            $collection = CollectionField::new($property, $pluginInfo->getName())
                ->setFormTypeOption('error_bubbling', false)
                ->setFormTypeOption('delete_empty', true)
                ->setEntryType($pluginInfo->getFormClass())
                ->setEntryIsComplex()
                ->renderExpanded()
                ->addCssClass($pluginInfo->getId() . '-plugin-collection')
                ->addCssClass('plugin-collection')
                ->hideOnIndex();

            $countAttribute = $reflection
                ->getProperty($property)
                ->getAttributes(Count::class, \ReflectionAttribute::IS_INSTANCEOF);
            $attr = ['data-plugin-collection-type' => $pluginInfo->getId()];
            if (!empty($countAttribute)) {
                $count = $countAttribute[0]->newInstance();
                if ($count->min !== null) {
                    $attr['data-plugin-collection-min'] = $count->min;
                }
                if ($count->max !== null) {
                    $attr['data-plugin-collection-max'] = $count->max;
                }
            }
            $collection->setFormTypeOption('row_attr', $attr);
            $fields[] = $collection;
        }

        return $fields;
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

    protected function setProjectToProjectMembers($entityInstance): void
    {
        foreach ($entityInstance->getProjectMembers() as $projectMember) {
            if ($projectMember->getProject()) {
                continue;
            }
            $projectMember->setProject($entityInstance);
        }
    }

    protected function removeUselessPluginType($entityInstance): void
    {
        $manager = $this->container->get(Manager::class);
        foreach ($manager->getPlugins() as $pluginInfo) {
            /**
             * @var Collection<int, PluginAbstract> $pluginCollection
             */
            $pluginCollection = $entityInstance->{'get'.mb_ucfirst($pluginInfo->getId()).'Plugins'}();
            foreach ($pluginCollection as $plugin) {
                $currentType = $plugin->getType();
                foreach ($pluginInfo->getTypes() as $typeInfo) {
                    if ($currentType === $typeInfo->getId()) {
                        continue;
                    }
                    $plugin->{'set'.mb_ucfirst($typeInfo->getId())}(null);
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
            Manager::class => '?'.Manager::class,
        ]);
    }
}
