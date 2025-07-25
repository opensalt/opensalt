<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * UserRepository.
 *
 * @method array findByOrg(AccessGroup $org)
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByUsername(string $username): ?User
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneBy(['username' => $identifier]);
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
