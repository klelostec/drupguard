<?php

namespace App\Controller;

use App\Form\InstallType;
use App\Form\Model\Install;
use Doctrine\DBAL\DriverManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallController extends AbstractController
{
    public function index(Request $request): Response
    {
        $install = new Install();
        $form = $this->createForm(InstallType::class, $install);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $test= TRUE;
        }
        return $this->render('install/index.html.twig', [
            'form' => $form,
        ]);
    }
}
