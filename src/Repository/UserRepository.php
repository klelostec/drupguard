<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function findByFirstOrLastName($value, $exclude= [])
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->andWhere('u.firstname LIKE :val OR u.lastname LIKE :val OR u.username LIKE :val OR CONCAT(u.firstname, \' \', u.lastname) LIKE :val')
            ->setParameter('val', $value . '%')
            ->orderBy('u.firstname', 'ASC')
            ->orderBy('u.lastname', 'ASC')
            ->setMaxResults(10);
        if (!empty($exclude)) {
            $queryBuilder->andWhere('u.id NOT IN (:exclude)')
                ->setParameter('exclude', $exclude);
        }
        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function findOneByTokenApi($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.token_api = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
