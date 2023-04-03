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
    #[Route('/', name: 'app_index')]
    public function index(TranslatorInterface $translator): Response
    {
        return $this->render('index/index.html.twig', [
            'controller_name' => $translator->trans('TOTfdqO %test%', ['%test%'=>'test'], NULL, 'fr'),
        ]);
    }
}
