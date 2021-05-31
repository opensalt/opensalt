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

    public function loadUserByIdentifier(string $identifier): ?User
    {
        $user = $this->findOneBy(['username' => $identifier]);

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
