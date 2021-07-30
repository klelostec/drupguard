<?php

namespace App\Controller;

use App\Form\ProfilePasswordType;
use App\Form\ProfileType;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class ProfileController extends AbstractController
{
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * @Route("/profile", name="app_profile")
     */
    public function profile(Request $request, UserPasswordHasherInterface $passwordEncoder, AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();
        $oldEmail = $user->getEmail();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            if ($oldEmail !== $user->getEmail()) {
                $user->setIsVerified(false);
                $this->addFlash('success', 'Please, check you email to confirm your email address.');
                // generate a signed url and email it to the user
                $this->emailVerifier->sendEmailConfirmation(
                    'app_verify_email',
                    $user,
                    (new TemplatedEmail())
                        ->from(new Address('no-reply@drupguard.com', 'DrupGuard'))
                        ->to($user->getEmail())
                        ->subject('Please Confirm your Email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );
            }

            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('profile/profile.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/profile_password", name="app_profile_password")
     */
    public function profilePassword(Request $request, UserPasswordHasherInterface $passwordEncoder): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfilePasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($form->get('password')->getData())) {
                // encode the plain password
                $user->setPassword(
                    $passwordEncoder->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $this->addFlash('success', 'Password has been changed.');
            }
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('profile/profile_password.html.twig', [
            'profilePasswordForm' => $form->createView(),
        ]);
    }

}
