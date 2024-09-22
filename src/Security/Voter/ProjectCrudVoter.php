<?php

namespace App\Security\Voter;

use App\Entity\ProjectMember;
use App\Entity\User;
use App\Security\ProjectRoles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProjectCrudVoter extends Voter
{
    /**
     * @var AccessDecisionManagerInterface
     */
    protected $decisionManager;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * UserVoter constructor.
     *
     * @param AccessDecisionManagerInterface $decisionManager
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, EntityManagerInterface $entityManager)
    {
        $this->decisionManager = $decisionManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject): bool
    {
        return strpos($attribute, 'PROJECT_') === 0;
    }

    /**
     * @param string $attribute
     * @param User $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (
            $this->decisionManager->decide($token, ['ROLE_ADMIN']) ||
            in_array($attribute, ['PROJECT_NEW', 'PROJECT_INDEX']) ||
            ($attribute === 'PROJECT_DETAIL' && $subject && $subject->isPublic())
        ) {
            return VoterInterface::ACCESS_GRANTED;
        }

        $result = VoterInterface::ACCESS_ABSTAIN;
        if (!$subject) {
            return $result;
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('pm')
            ->from(ProjectMember::class, 'pm')
            ->leftJoin('pm.user', 'pmu')
            ->leftJoin('pm.groups', 'pmg')
            ->leftJoin('pmg.users', 'pmgu')
            ->where('pm.project = :projectId AND (pmu.id = :userId OR pmgu.id = :userId)')
            ->setParameter('userId', $token->getUser()->getId())
            ->setParameter('projectId', $subject->getId())
            ->groupBy('pm.id');

        $pmRes = $qb->getQuery()->getResult();
        $read = $write = false;
        foreach ($pmRes as $pm) {
            /**
             * @var ProjectMember $pm
             */
            if (in_array($pm->getRole(), [ProjectRoles::OWNER, ProjectRoles::MAINTAINER])) {
                $read = $write = true;
                break;
            }
            elseif ($pm->getRole() === ProjectRoles::USER) {
                $read = true;
            }
        }

        if (
            ($attribute === 'PROJECT_DETAIL' && $read) ||
            $write
        ){
            $result = VoterInterface::ACCESS_GRANTED;
        }

        return $result;
    }
}