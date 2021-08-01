<?php

namespace App\Security\Voter;

use App\Entity\Project;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProjectVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['PROJECT_SHOW', 'PROJECT_EDIT', 'PROJECT_DELETE', 'PROJECT_RUN', 'PROJECT_EMAIL'])
            && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'PROJECT_SHOW':
                return $this->canRead($user, $subject);
            case 'PROJECT_EDIT':
            case 'PROJECT_DELETE':
            case 'PROJECT_RUN':
            case 'PROJECT_EMAIL':
                return $this->canWrite($user, $subject);
        }

        return true;
    }

    protected function canRead(UserInterface $user, Project $project)
    {
        return $project->isPublic() ||
            $this->canWrite($user, $project);
    }

    protected function canWrite(UserInterface $user, Project $project)
    {
        return $user->isSuperAdmin() ||
            $project->getOwner()->getId() === $user->getId() ||
            $project->getAllowedUsers()->contains($user);
    }
}
