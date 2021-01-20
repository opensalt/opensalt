<?php

namespace App\Repository\User;

use App\Entity\User\Organization;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * UserRepository.
 *
 * @method array findByOrg(Organization $org)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Loads the user for the given username.
     *
     * This method must return null if the user is not found.
     *
     * @param string $username The username
     */
    public function loadUserByUsername($username): ?User
    {
        $user = $this->findOneBy(['username' => $username]);

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * Find all admin user per organization.
     */
    public function findAdmins(): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.roles LIKE :roles')
            ->groupBy('u.org')
            ->setParameter('roles', '%"ROLE_ADMIN"%');

        return $qb->getQuery()->getResult();
    }
}
