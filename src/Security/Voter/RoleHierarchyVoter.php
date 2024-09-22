<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RoleHierarchyVoter extends RoleVoter
{
    private RoleHierarchyInterface $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy, string $prefix = 'ROLE_')
    {
        $this->roleHierarchy = $roleHierarchy;

        parent::__construct($prefix);
    }

    protected function extractRoles(TokenInterface $token): array
    {
        $user = $token->getUser();
        $roles = $token->getRoleNames();
        if ($user instanceof User) {
            foreach ($user->getGroups() as $group) {
                $roles = array_merge($roles, $group->getRoles());
            }
            $roles = array_unique($roles);
        }
        $roles = $this->roleHierarchy->getReachableRoleNames($roles);
        return $roles;
    }
}
