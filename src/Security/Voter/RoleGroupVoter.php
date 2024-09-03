<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RoleGroupVoter implements CacheableVoterInterface
{
    private RoleHierarchyInterface $roleHierarchy;

    private string $prefix;

    public function __construct(RoleHierarchyInterface $roleHierarchy, string $prefix = 'ROLE_')
    {
        $this->roleHierarchy = $roleHierarchy;

        $this->prefix = $prefix;
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        $roles = $this->extractRoles($token);

        foreach ($attributes as $attribute) {
            if (!\is_string($attribute) || !str_starts_with($attribute, $this->prefix)) {
                continue;
            }

            $result = VoterInterface::ACCESS_DENIED;
            if (\in_array($attribute, $roles, true)) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return $result;
    }

    public function supportsAttribute(string $attribute): bool
    {
        return str_starts_with($attribute, $this->prefix);
    }

    public function supportsType(string $subjectType): bool
    {
        return true;
    }

    protected function extractRoles(TokenInterface $token): array
    {
        $user = $token->getUser();
        $roles = [];
        if ($user instanceof User) {
            foreach ($user->getGroups() as $group) {
                $roles = array_merge($roles, $group->getRoles());
            }
            $roles = array_unique($roles);
            $roles = $this->roleHierarchy->getReachableRoleNames($roles);
        }

        return $roles;
    }
}
