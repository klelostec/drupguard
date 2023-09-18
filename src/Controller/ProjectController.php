<?php

namespace App\Controller;

use App\Entity\Analyse;
use App\Entity\AnalyseItem;
use App\Entity\AnalyseQueue;
use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\AnalyseRepository;
use App\Repository\ProjectRepository;
use App\Service\AnalyseHelper;
use App\Service\MachineNameHelper;
use App\Service\StatsHelper;
use Cz\Git\GitException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/project")
 */
class ProjectController extends AbstractController
{
    /**
     * @Route("/", name="project_index", methods={"GET"})
     */
    public function index(Request $request, ProjectRepository $projectRepository): Response
    {
        $page = $request->query->get('page', 0);
        $nbItems = $projectRepository->countByAllowedUser($this->getUser());
        $limit = 12;
        return $this->render('project/index.html.twig', [
            'currentPage' => $page,
            'nbPages' => ceil($nbItems/$limit),
            'projects' => $projectRepository->findByAllowedUser($this->getUser(), $page, $limit),
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/new", name="project_new", methods={"GET","POST"})
     */
    public function new(Request $request, MachineNameHelper $machineNameHelper, ManagerRegistry $managerRegistry): Response
    {
        $form = $this->createForm(ProjectType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var Project $project
             */
            $project = $form->getData();
            if ($project->isPublic() && !empty($project->getAllowedUsers())) {
                $project->removeAllAllowedUser();
            }
            if (empty($project->getOwner())) {
                $project->setOwner($this->getUser());
            }
            if (empty($project->getMachineName())) {
                $machineName = $machineNameHelper->getMachineName($project->getName());
                $project->setMachineName($machineName);
            }
            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($project);
            $entityManager->flush();

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/new.html.twig', [
            'projectForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{analyse}", name="project_show", priority=1, requirements={"id"="\d+", "analyse"="\d*"}, defaults={"analyse"=""}, methods={"GET"})
     * @IsGranted("PROJECT_SHOW", subject="project")
     */
    public function show(Project $project, Analyse $analyse = null, StatsHelper $statsHelper, AnalyseRepository $analyseRepository): Response
    {
        if ($analyse && ($analyse->getProject()->getId() !== $project->getId() || $analyse->isRunning())) {
            throw new NotFoundHttpException();
        }

        if (!$analyse) {
            $analyse = $project->getLastAnalyse();
            if ($analyse && $analyse->isRunning()) {
                $analyse = $analyseRepository->findOneBy(['project' => $project->getId(), 'isRunning' => false], ['date' => 'DESC']);
            }
        }

        $prevAnalyse = $nextAnalyse = null;
        if ($analyse) {
            $prevAnalyse = $analyseRepository->getPreviousAnalyse($analyse);
            $nextAnalyse = $analyseRepository->getNextAnalyse($analyse);
        }

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'analyse' => $analyse,
            'prevAnalyse' => $prevAnalyse,
            'nextAnalyse' => $nextAnalyse,
            'statsDonut' => $analyse ? $statsHelper->buildProjectDonut($analyse) : [],
            'statsHistory' => $analyse ? $statsHelper->buildProjectHistory($project) : [
                'data' => [
                    ['success'],
                    ['warning'],
                    ['other'],
                    ['danger']
                ],
                'categories' => []
            ],
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/{id}/history", name="project_history", methods={"GET"})
     */
    public function history(Project $project, Request $request, AnalyseRepository $analyseRepository, StatsHelper $statsHelper): Response
    {
        $page = $request->query->get('page', 0);
        $nbItems = $analyseRepository->countByProject($project);
        $limit = 20;
        return $this->render('project/history.html.twig', [
            'currentPage' => $page,
            'nbPages' => ceil($nbItems/$limit),
            'project' => $project,
            'analyse' => null,
            'statsHelper' => $statsHelper,
            'analyses' => $analyseRepository->findByProject($project, $page, $limit),
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="project_edit", methods={"GET","POST"})
     * @IsGranted("PROJECT_EDIT", subject="project")
     */
    public function edit(Request $request, Project $project, ManagerRegistry $managerRegistry): Response
    {
        if ($project->isPublic() && !empty($project->getAllowedUsers())) {
            $project->removeAllAllowedUser();
        }
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $managerRegistry->getManager()->flush();

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/edit.html.twig', [
            'projectForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="project_delete", methods={"GET","POST"})
     * @IsGranted("PROJECT_DELETE", subject="project")
     */
    public function delete(Request $request, Project $project, KernelInterface $kernel, ManagerRegistry $managerRegistry): Response
    {
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $fileSystem = new Filesystem();
            $workspaceDir = $kernel->getProjectDir() . '/workspace/' . $project->getMachineName();
            if ($fileSystem->exists($workspaceDir)) {
                $fileSystem->remove($workspaceDir);
            }

            $entityManager = $managerRegistry->getManager();
            $entityManager
                ->createQuery('UPDATE App\Entity\Project p SET p.lastAnalyse=NULL WHERE p.id = :project_id')
                ->setParameter('project_id', $project->getId())
                ->execute();
            $entityManager
                ->createQuery('DELETE App\Entity\AnalyseItem ai WHERE ai.analyse IN (SELECT a.id FROM App\Entity\Analyse a WHERE a.project = :project_id)')
                ->setParameter('project_id', $project->getId())
                ->execute();
            $entityManager
                ->createQuery('DELETE App\Entity\Analyse a WHERE a.project = :project_id')
                ->setParameter('project_id', $project->getId())
                ->execute();

            $project->setLastAnalyse(NULL);
            $entityManager->remove($project);
            $entityManager->flush();

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/delete.html.twig', [
          'project' => $project
        ]);
    }

    /**
     * @Route("/{id}/run", name="project_run", methods={"GET"})
     * @IsGranted("PROJECT_RUN", subject="project")
     */
    public function run(Project $project, AnalyseHelper $analyseHelper, ManagerRegistry $managerRegistry): Response
    {
        if ($project->isPending() || ($project->getLastAnalyse() && $project->getLastAnalyse()->isRunning())) {
            return new JsonResponse(['return' => false]);
        }

        if (!$project->isPending()) {
            $queue = new AnalyseQueue();
            $queue->addProject($project);

            $entityManager = $managerRegistry->getManager();
            $entityManager->persist($queue);
            $entityManager->flush();
        }

        return new JsonResponse(['return' => true]);
    }

    /**
     * @Route("/ajax/git-branches", name="project_ajax_git_branches")
     */
    public function ajaxGitBranches(Request $request): Response
    {
        try {
            $project = new Project();
            $project->setGitRemoteRepository($request->query->get('repo'));
            if($branch = $request->query->get('branch')) {
                $project->setGitBranch($branch);
            }
            $form = $this->createForm(ProjectType::class, $project);
            // no field? Return an empty response
            if (!$form->has('gitBranch')) {
                $ret = '';
            }
            else {
                $ret = $this->renderView('project/_form_git_branch.html.twig', [
                    'projectForm' => $form->createView(),
                ]);
            }
            return $this->json(['html' => $ret]);
        }
        catch(GitException $e) {
            return $this->json(['error' => $e->getMessage()]);
        }

    }

    /**
     * @Route("/ajax/machine-name", name="project_ajax_machine_name")
     */
    public function ajaxMachineName(Request $request, MachineNameHelper $machineNameHelper): Response
    {
        $name = (string) $request->query->get('name');
        $name = $machineNameHelper->getMachineName($name);
        return new JsonResponse($name);
    }

    /**
     * @Route("/{id}/check", name="project_check", methods={"GET"})
     */
    public function check(Request $request, Project $project): Response
    {
        $analyse = $project->getLastAnalyse();
        return new JsonResponse([
          'running' => $analyse && $analyse->isRunning(),
          'pending' => $project->isPending()
        ]);
    }

    /**
     * @Route("/{id}/{analyse}/email", name="project_email", methods={"GET"})
     * @IsGranted("PROJECT_EMAIL", subject="project")
     */
    public function email(Project $project, Analyse $analyse, AnalyseHelper $analyseHelper): Response
    {
        $analyseHelper->emailReport($project, $analyse);

        return new JsonResponse(['return' => true]);
    }
}
