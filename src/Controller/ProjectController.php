<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Service\AnalyseHelper;
use App\Service\GitHelper;
use App\Service\MachineName;
use App\Service\MachineNameHelper;
use App\Service\StatsHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/project")
 * @Security("is_granted('ROLE_USER')")
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
        $limit = 10;
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
    public function new(Request $request, MachineNameHelper $machineNameHelper): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if(empty($project->getOwner())) {
                $project->setOwner($this->getUser());
            }
            if(empty($project->getMachineName())) {
                $machineName = $machineNameHelper->getMachineName($project->getMachineName());
                $project->setMachineName($machineName);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($project);
            $entityManager->flush();

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="project_show", methods={"GET"})
     */
    public function show(Project $project, StatsHelper $statsHelper): Response
    {
        if(!$project->isReadable($this->getUser())) {
            throw new AccessDeniedException('Cannot edit project.');
        }

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'statsDonut' => $statsHelper->buildProjectDonut($project),
            'statsHistory' => $statsHelper->buildProjectHistory($project),
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="project_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Project $project): Response
    {
        if(!$project->isWritable($this->getUser())) {
            throw new AccessDeniedException('Cannot edit project.');
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="project_delete", methods={"GET","POST"})
     */
    public function delete(Request $request, Project $project): Response
    {
        if(!$project->isWritable($this->getUser())) {
            throw new AccessDeniedException('Cannot edit project.');
        }

        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $fileSystem = new Filesystem();
            $workspaceDir = $this->get('kernel')->getProjectDir() . '/workspace/' . $project->getMachineName();
            if($fileSystem->exists($workspaceDir)) {
                $fileSystem->remove($workspaceDir);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($project);
            $entityManager->flush();
        }

        return $this->render('project/delete.html.twig', [
          'project' => $project
        ]);
    }

    /**
     * @Route("/{id}/run", name="project_run", methods={"GET"})
     */
    public function run(Project $project, AnalyseHelper $analyseHelper): Response
    {
        if(!$project->isWritable($this->getUser())) {
            throw new AccessDeniedException('Cannot edit project.');
        }

        $analyseHelper->start($project, true);

        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }

    /**
     * @Route("/ajax/git-branches", name="project_ajax_git_branches", methods={"POST"})
     */
    public function ajaxGitBranches(Request $request): Response {
        $branches = [];
        if($gitRemoteRepository = $request->request->get('gitRemoteRepository')) {
            $branches = GitHelper::getRemoteBranchesWithoutCheckout($gitRemoteRepository);
        }
        return new JsonResponse($branches);
    }
}
