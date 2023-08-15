<?php

namespace App\Controller;

use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Exception\DatabaseDoesNotExist;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class IndexController extends AbstractController
{
    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('app_index_locale', ['_locale' => $this->getParameter('app.default_locale')]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/', name: 'app_index_locale')]
    public function index(): Response
    {
        return $this->render('index/index.html.twig', [
            'controller_name' => 'index',
        ]);
    }
}
