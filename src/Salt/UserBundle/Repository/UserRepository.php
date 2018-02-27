<?php

namespace Salt\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * UserRepository
 *
 * @method array findByOrg(Organization $org)
 */
class UserRepository extends EntityRepository implements UserLoaderInterface
{
    /**
     * Loads the user for the given username.
     *
     * This method must return null if the user is not found.
     *
     * @param string $username The username
     *
     * @return User|null
     */
    public function loadUserByUsername($username): ?User
    {
        $user = $this->findOneBy(['username'=>$username]);

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * Find all admin user per organization.
     *
     * @return array
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
