<?php

namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Security\ProjectRoles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    protected UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $groupsDef = [
            [
                'name' => 'Administrator',
                'roles' => ['ROLE_ADMIN'],
            ],
            [
                'name' => 'Project 1',
                'roles' => [],
            ],
        ];
        foreach ($groupsDef as $index => $currentGroup) {
            $group = new Group();
            $group->setName($currentGroup['name']);
            $group->setRoles($currentGroup['roles']);
            $manager->persist($group);
            $groupsDef[$index] = $group;
        }
        $manager->flush();

        $usersDef = [
            [
                'username' => 'admin',
                'password' => 'admin',
                'email' => 'admin@drupguard.com',
                'groups' => [],
                'roles' => ['ROLE_SUPER_ADMIN'],
            ],
            [
                'username' => 'user1',
                'password' => 'user1',
                'email' => 'user1@drupguard.com',
                'groups' => [$groupsDef[0]],
                'roles' => [],
            ],
            [
                'username' => 'user2',
                'password' => 'user2',
                'email' => 'user2@drupguard.com',
                'groups' => [$groupsDef[1]],
                'roles' => [],
            ],
            [
                'username' => 'user3',
                'password' => 'user3',
                'email' => 'user3@drupguard.com',
                'groups' => [],
                'roles' => [],
            ],
        ];
        foreach ($usersDef as $index => $currentUser) {
            $user = new User();
            $user->setUsername($currentUser['username']);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $currentUser['password']));
            $user->setEmail($currentUser['email']);
            $user->setVerified(true);
            foreach ($currentUser['groups'] as $userGroup) {
                $userGroup->addUser($user);
                $manager->persist($userGroup);
            }
            $user->setRoles($currentUser['roles']);
            $manager->persist($user);
            $usersDef[$index] = $user;
        }

        $projectsDef = [
            [
                'name' => 'project1',
                'machine_name' => 'project1',
                'isPublic' => true,
            ],
            [
                'name' => 'project2',
                'machine_name' => 'project2',
                'isPublic' => false,
            ],
            [
                'name' => 'project3',
                'machine_name' => 'project3',
                'isPublic' => false,
            ],
            [
                'name' => 'project4',
                'machine_name' => 'project4',
                'isPublic' => false,
            ],
        ];

        foreach ($projectsDef as $index => $currentProject) {
            $project = new Project();
            $project->setName($currentProject['name']);
            $project->setMachineName($currentProject['machine_name']);
            $project->setIsPublic($currentProject['isPublic']);

            $manager->persist($project);
            $projectsDef[$index] = $project;
        }

        $projectMembersDef = [
            [
                'project' => $projectsDef[0],
                'user' => $usersDef[0],
                'groups' => null,
                'role' => ProjectRoles::OWNER,
            ],
            [
                'project' => $projectsDef[1],
                'user' => $usersDef[0],
                'groups' => null,
                'role' => ProjectRoles::OWNER,
            ],
            [
                'project' => $projectsDef[1],
                'user' => $usersDef[2],
                'groups' => null,
                'role' => ProjectRoles::USER,
            ],
            [
                'project' => $projectsDef[2],
                'user' => $usersDef[0],
                'groups' => null,
                'role' => ProjectRoles::OWNER,
            ],
            [
                'project' => $projectsDef[2],
                'user' => null,
                'groups' => $groupsDef[1],
                'role' => ProjectRoles::USER,
            ],
            [
                'project' => $projectsDef[3],
                'user' => $usersDef[0],
                'groups' => null,
                'role' => ProjectRoles::OWNER,
            ],
            [
                'project' => $projectsDef[3],
                'user' => $usersDef[2],
                'groups' => null,
                'role' => ProjectRoles::MAINTAINER,
            ],
            [
                'project' => $projectsDef[3],
                'user' => $usersDef[3],
                'groups' => null,
                'role' => ProjectRoles::USER,
            ],
        ];

        foreach ($projectMembersDef as $index => $currentMemberProject) {
            $projectMember = new ProjectMember();
            $projectMember->setProject($currentMemberProject['project']);
            $projectMember->setUser($currentMemberProject['user']);
            $projectMember->setGroups($currentMemberProject['groups']);
            $projectMember->setRole($currentMemberProject['role']);

            $manager->persist($projectMember);
            $projectMembersDef[$index] = $projectMember;
        }

        $manager->flush();
    }
}
