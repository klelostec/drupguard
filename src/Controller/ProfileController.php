<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale<%app.supported_locales%>}/profile')]
class ProfileController extends AbstractUserController
{

    #[Route('/', name: 'app_profile_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $initMail = $user->getEmail();
        $form = $this->createForm(UserType::class, $user, ['mode' => 'edit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($form->get('plainPassword')->getData())) {
                $user->setPassword($this->getHashPassword($user, $form->get('plainPassword')->getData()));
            }
            if ($initMail != $user->getEmail()) {
                $user->setIsVerified(false);
                $this->sendRegistrationMail($user);
            }
            $entityManager->flush();

            if($initMail != $user->getEmail()) {
                $this->addFlash('success', 'Your email address has been changed.');
                return $this->redirectToRoute('app_logout');
            }
            else {
                return $this->redirectToRoute('app_index');
            }
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}
