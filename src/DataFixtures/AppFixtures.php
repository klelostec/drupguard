<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    protected $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder) {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user
          ->setUsername('admin')
          ->setFirstname('admin')
          ->setLastname('admin')
          ->setIsVerified(true)
          ->setEmail('admin@drupguard.com')
          ->setPassword(
            $this->passwordEncoder->encodePassword(
              $user,
              'admin'
            )
          )
          ->setRoles(['ROLE_ADMIN']);

        $manager->persist($user);
        $manager->flush();


        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
