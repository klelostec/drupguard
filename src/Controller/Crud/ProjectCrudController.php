<?php

namespace App\Controller\Crud;

use App\AnalyseLevelState;
use App\EasyAdmin\Field\MachineNameField;
use App\EasyAdmin\Filter\AnalyseLevelStateFilter;
use App\Entity\Plugin\PluginAbstract;
use App\Entity\Project;
use App\Entity\Report;
use App\Entity\Report\ReportAbstract;
use App\Message\ProjectAnalysePending;
use App\Plugin\Manager;
use App\Repository\ReportRepository;
use App\Security\Roles;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Count;

use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use function Symfony\Component\String\u;
use function Symfony\Component\Translation\t;

class ProjectCrudController extends AbstractCrudController
{
    protected AdminUrlGeneratorInterface $adminUrlGenerator;
    protected ReportRepository $reportRepository;
    protected EntityPaginator $entityPaginator;

    protected ChartBuilderInterface $chartBuilder;

    public function __construct(AdminUrlGenerator $adminUrlGenerator, ReportRepository $reportRepository, EntityPaginator $entityPaginator, ChartBuilderInterface $chartBuilder)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->reportRepository = $reportRepository;
        $this->entityPaginator = $entityPaginator;
        $this->chartBuilder = $chartBuilder;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('form/types/custom.html.twig')
            ->overrideTemplate('crud/detail', 'admin/project/crud/detail.html.twig')
            ->showEntityActionsInlined()
        ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return parent::configureAssets($assets)
            ->addAssetMapperEntry('app')
            ->addAssetMapperEntry('machine_name')
            ->addAssetMapperEntry('plugin_settings')
            ->addAssetMapperEntry('project_running')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(AnalyseLevelStateFilter::new('lastReportState', t('Last Report State'))
                ->setFormTypeOption('mapped', false))
        ;
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        if (Crud::PAGE_DETAIL === $responseParameters->get('pageName')) {
            $entity = $responseParameters->get('entity');
            $queryBuilder = $this->reportRepository->createQueryBuilder('report');
            $queryBuilder
                ->where('report.project = :id')
                ->setParameter('id', $entity->getInstance()->getId())
                ->orderBy('report.datetime', 'DESC');
            $paginatorDto = new PaginatorDto(1, 5, 1, true, null);
            $paginatorDto->setPageNumber((int) $this->getContext()->getRequest()->query->get('page', '1'));
            $paginator = $this->entityPaginator->paginate($paginatorDto, $queryBuilder);
            $report = $chart = null;
            if (!$paginator->isOutOfRange()) {
                /**
                 * @var Report $report
                 */
                $report = $paginator->getResults()->current();
                $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
                $chartData = [
                    'labels' => ['Success', 'Warning', 'Danger'],
                    'datasets' => [],
                ];

                $colors = [
                    AnalyseLevelState::getColor(AnalyseLevelState::SUCCESS),
                    AnalyseLevelState::getColor(AnalyseLevelState::WARNING),
                    AnalyseLevelState::getColor(AnalyseLevelState::DANGER),
                    AnalyseLevelState::getColor(AnalyseLevelState::SECURITY),
                ];
                foreach ($report->getOrderedReports() as $currentReport) {
                    /**
                     * @var ReportAbstract $currentReport
                     */
                    $chartData['labels'][] = $currentReport->getName();
                    $data = [];
                    foreach ($currentReport->getItems() as $reportItem) {
                        /**
                         * @var Report\ReportItemAbstract $reportItem
                         */
                        if (!isset($data[$reportItem->getState()->value])) {
                            $data[$reportItem->getState()->value] = 0;
                        }
                        $data[$reportItem->getState()->value]++;
                    }

                    $chartData['datasets'][] = [
                        'backgroundColor' => $colors,
                        'data' => array_values($data),
                    ];
                }
                $chart->setData($chartData);

                $chart->setOptions([
                    'responsive' => TRUE,
                ]);
            }
            $responseParameters->set('report', $report);
            $responseParameters->set('paginator', $paginator);
            $responseParameters->set('chart', $chart);
        }

        return $responseParameters;
    }

    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $analyseAction = Action::new('analyse', t('Analyse'), 'fa-solid fa-play')
            ->linkToCrudAction('analyse')
            ->displayAsLink()
            ->setTemplatePath('admin/project/action/analyse.html.twig')
            ->addCssClass('text-dark project-running-action visually-hidden')
            ->displayIf(static function (Project $entity) {
                return !$entity->getAnalysePlugins()->isEmpty();
            });

        return $actions
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('internal:delete');
            })
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            // ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->add(Action::INDEX, $analyseAction)
            ->add(Action::DETAIL, $analyseAction)
            ->setPermission(Action::BATCH_DELETE, 'PROJECT_DELETE')
            ->setPermission(Action::DELETE, 'PROJECT_DELETE')
            ->setPermission(Action::DETAIL, 'PROJECT_DETAIL')
            ->setPermission(Action::EDIT, 'PROJECT_EDIT')
            ->setPermission(Action::INDEX, 'PROJECT_INDEX')
            ->setPermission(Action::NEW, 'PROJECT_NEW')
            ->setPermission('analyse', 'PROJECT_ANALYSE')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            FormField::addTab('General'),
            IdField::new('id')
                ->hideOnIndex()
                ->hideOnForm(),
            ImageField::new('logo')
                ->setUploadDir('public/media/projects')
                ->setBasePath('media/projects')
                ->setTemplatePath('admin/fields/project/logo.html.twig'),
            TextField::new('name')
                ->hideOnIndex(),
            TextField::new('name')
                ->renderAsHtml()
                ->formatValue(function ($value, $entityDto) {
                    return '<a href="'.$this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($entityDto->getId()).'">'.$value.'</a>';
                })
                ->hideOnForm()
                ->hideOnDetail(),
            MachineNameField::new('machine_name')
                ->setFormTypeOption('source_field', 'name')
                ->hideOnIndex()
                ->hideWhenUpdating(),
            CollectionField::new('projectMembers')
                ->useEntryCrudForm(ProjectMemberCrudController::class)
                ->setEntryIsComplex()
                ->hideOnIndex()
            // ->hideWhenCreating()
            ,
            BooleanField::new('isPublic')
                ->hideOnIndex()
                ->hideOnDetail(),
            BooleanField::new('isPublic')
                ->renderAsSwitch(false)
                ->hideOnForm()
                ->hideOnIndex(),
            Field::new('lastReport', 'Last report')
                ->setTemplatePath('admin/fields/report/state.html.twig')
                ->formatValue(function (?Report $report = null) {
                    return $report;
                })
                ->hideOnForm(),
            FormField::addTab('Plugins'),
        ];

        $manager = $this->container->get(Manager::class);
        $reflection = new \ReflectionClass(Project::class);
        foreach ($manager->getPlugins() as $pluginInfo) {
            $property = $pluginInfo->getId().'Plugins';
            $collection = CollectionField::new($property, $pluginInfo->getName())
                ->setFormTypeOption('error_bubbling', false)
                ->setFormTypeOption('delete_empty', true)
                ->setEntryType($pluginInfo->getFormClass())
                ->setEntryIsComplex()
                ->renderExpanded()
                ->addCssClass($pluginInfo->getId().'-plugin-collection')
                ->addCssClass('plugin-collection')
                ->hideOnIndex();

            $countAttribute = $reflection
                ->getProperty($property)
                ->getAttributes(Count::class, \ReflectionAttribute::IS_INSTANCEOF);
            $attr = ['data-plugin-collection-type' => $pluginInfo->getId()];
            if (!empty($countAttribute)) {
                $count = $countAttribute[0]->newInstance();
                if (null !== $count->min) {
                    $attr['data-plugin-collection-min'] = $count->min;
                }
                if (null !== $count->max) {
                    $attr['data-plugin-collection-max'] = $count->max;
                }
            }
            $collection->setFormTypeOption('row_attr', $attr);
            $fields[] = $collection;
        }

        $fields[] = FormField::addTab('Analyse settings');
        $fields[] = TextField::new('periodicity')
            ->setFormTypeOptions([
                'attr' => [
                    'placeholder' => '0 5 * * 4',
                ],
                'help' => 'More help at <a href="https://crontab.guru/" target="_blank">crontab.guru</a>',
                'help_html' => true,
            ])
            ->hideOnIndex();
        $fields[] = ChoiceField::new('emailLevel')
            ->hideOnIndex();
        $fields[] = TextareaField::new('emailExtra')
            ->hideOnIndex();

        return $fields;
    }

    #[AdminAction(routePath: '/{entityId}/analyse', routeName: 'analyse')]
    public function analyse(AdminContext $context, MessageBusInterface $bus)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => 'analyse', 'entity' => $context->getEntity()])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $entityInstance = $context->getEntity()->getInstance();
        $res = true;
        try {
            $bus->dispatch(new ProjectAnalysePending($entityInstance->getId()));
        } catch (ForeignKeyConstraintViolationException $e) {
            $res = false;
        }

        return new JsonResponse([
            'result' => $res,
        ]);
    }

    #[Route('/project/check-running', name: 'app_project_check_running', format: 'json', methods: ['POST'], )]
    public function checkIsRunning(Request $request)
    {
        /**
         * @var EntityManagerInterface $em
         */
        $em = $this->container->get('doctrine')->getManagerForClass(Project::class);
        $repository = $em->getRepository(Project::class);
        $ret = [];
        foreach ($request->toArray() as $entityId) {
            $entityInstance = $repository->find($entityId);
            if (!$entityInstance) {
                continue;
            }
            if (!$this->isGranted('PROJECT_ANALYSE', $entityInstance)) {
                throw new AccessDeniedHttpException();
            }
            $ret[$entityInstance->getId()] = $entityInstance->isRunning();
        }

        return new JsonResponse([
            'result' => $ret,
        ]);
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

    protected function removeUselessPluginType($entityInstance): void
    {
        $manager = $this->container->get(Manager::class);
        foreach ($manager->getPlugins() as $pluginInfo) {
            $fieldIdentifier = mb_ucfirst(u($pluginInfo->getId())->camel());
            /**
             * @var Collection<int, PluginAbstract> $pluginCollection
             */
            $pluginCollection = $entityInstance->{'get'.$fieldIdentifier.'Plugins'}();
            foreach ($pluginCollection as $plugin) {
                $currentType = $plugin->getType();
                foreach ($pluginInfo->getTypes() as $typeInfo) {
                    if ($currentType === $typeInfo->getId()) {
                        continue;
                    }
                    $plugin->{'set'.mb_ucfirst(u($typeInfo->getId())->camel())}(null);
                }
            }
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->removeUselessPluginType($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->removeUselessPluginType($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            Manager::class => '?'.Manager::class,
        ]);
    }
}
