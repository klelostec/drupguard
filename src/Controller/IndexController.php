<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->forward('App\Controller\SecurityController::login');
        }

        return $this->forward('App\Controller\ProjectController::index');
    }
}
