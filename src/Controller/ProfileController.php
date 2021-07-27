<?php

namespace App\Controller;

use App\Form\ProfileFormType;
use App\Security\EmailVerifier;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class ProfileController extends AbstractController
{
    private $emailVerifier;
    private $requestStack;

    public function __construct(EmailVerifier $emailVerifier, RequestStack $requestStack)
    {
        $this->emailVerifier = $emailVerifier;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/profile", name="app_profile")
     * @Security("is_granted('ROLE_USER')")
     */
    public function profile(Request $request, UserPasswordHasherInterface $passwordEncoder, AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();
        if (null === $user) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ProfileFormType::class, $user);
        $oldEmail = $user->getEmail();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($form->get('plainPassword')->getData())) {
                // encode the plain password
                $user->setPassword(
                    $passwordEncoder->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }
            if ($oldEmail != $user->getEmail()) {
                $user->setIsVerified(false);
            }

            $this->getDoctrine()->getManager()->flush();

            if ($oldEmail != $user->getEmail()) {
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
        }

        return $this->render('profile/profile.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }
}
