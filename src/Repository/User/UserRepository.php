<?php

namespace App\Repository\User;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Entity\User\Organization;
use App\Entity\User\User;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * UserRepository
 *
 * @method array findByOrg(Organization $org)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

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

    /**
     * Pending status & new register user on Top of user list.
     *
     * @return array
     */
    public function findAll()
    {

        return $this->findBy(array(), array('status' => 'DESC', 'id' => 'DESC'));

    }

    /**
     * Admin login Pending status & new register user on Top of user list.
     *
     * @return array
     */
    public function findAdminOrg($user_org)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.org = :org')
            ->setParameter('org', $user_org)
            ->addOrderBy('u.status', 'DESC')
            ->addOrderBy('u.id', 'DESC');
        
        return $qb->getQuery()->getResult();
    }

}
