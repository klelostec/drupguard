<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Entity\Project;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    /**
     * @Route("/projects", name="api_list_projects", methods={"GET", "OPTIONS"})
     * @OA\Response(
     *     response=200,
     *     description="Returns all projects with their last analysis and items.",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Project::class, groups={"list_projects"}))
     *     )
     * )
     * @OA\Tag(name="project")
     */
    public function index(ProjectRepository $projectRepository, SerializerInterface $serializer): Response
    {
        $projects = $projectRepository->findAllByAllowedUser($this->getUser());
        $json = $serializer->serialize($projects, 'json', ['groups' => 'list_projects']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
