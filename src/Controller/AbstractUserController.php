<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Translation\LocaleSwitcher;

class AbstractUserController extends AbstractController
{
    protected EmailVerifier $emailVerifier;
    protected LocaleSwitcher $localeSwitcher;
    protected UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(EmailVerifier $emailVerifier, LocaleSwitcher $localeSwitcher, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->emailVerifier = $emailVerifier;
        $this->localeSwitcher = $localeSwitcher;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    protected function sendRegistrationMail(User $user) {
        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('no-reply@drupguard.com', 'Drupguard'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig'),
            ['_locale' => $this->localeSwitcher->getLocale()]
        );
        // do anything else you need here, like send an email
    }

    protected function getHashPassword(User $user, string $plainPassword): string {
        return $this->userPasswordHasher->hashPassword(
            $user,
            $plainPassword
        );
    }
}
