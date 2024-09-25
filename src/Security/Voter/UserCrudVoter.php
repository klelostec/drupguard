<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class UserCrudVoter extends Voter
{
    /**
     * @var AccessDecisionManagerInterface
     */
    protected $decisionManager;

    /**
     * UserVoter constructor.
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * @param string $attribute
     */
    protected function supports($attribute, $subject): bool
    {
        return 0 === strpos($attribute, 'USER_');
    }

    /**
     * @param string $attribute
     * @param User   $subject
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return VoterInterface::ACCESS_GRANTED;
        }

        if (in_array($attribute, ['USER_EDIT', 'USER_DETAIL']) && $subject->getId() === $token->getUser()->getId()) {
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}
