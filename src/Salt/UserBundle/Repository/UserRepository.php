<?php

namespace Salt\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * UserRepository
 *
 * @method array findByOrg(Organization $org)
 */
class UserRepository extends EntityRepository implements UserLoaderInterface
{
    /**
     * @var UserPasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @DI\InjectParams({"encoder" = @DI\Inject("security.password_encoder")})
     *
     * @param UserPasswordEncoderInterface $encoder
     */
    public function setEncoder(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
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
     * Creates a user
     *
     * @param string $username
     * @param Organization $org
     * @param string|null $plainPassword
     * @param string|null $role
     *
     * @return string The user's password
     */
    public function addNewUser(string $username, Organization $org, ?string $plainPassword = null, ?string $role = null): string
    {
        if (empty(trim($plainPassword))) {
            // if there is no password, make something ugly up
            $plainPassword = rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '=');
        }

        if (null === $role) {
            $role = 'ROLE_USER';
        }
        if (0 !== strpos($role, 'ROLE_')) {
            $role = 'ROLE_'.preg_replace('/[^A-Z]/', '_', strtoupper($role));
        }

        if (!in_array($role, array_merge(User::USER_ROLES, ['ROLE_USER']))) {
            throw new \InvalidArgumentException("Role {$role} is not a valid role.");
        }

        $user = new User($username);
        $user->setOrg($org);
        $password = $this->encoder->encodePassword($user, $plainPassword);
        $user->setPassword($password);
        $user->addRole($role);

        $this->getEntityManager()->persist($user);

        return $plainPassword;
    }

    /**
     * Sets the password for a user
     *
     * @param string $username
     * @param string|null $plainPassword
     *
     * @return string The user's password
     */
    public function setUserPassword(string $username, ?string $plainPassword = null): string
    {
        if (empty(trim($plainPassword))) {
            // if there is no password, make something ugly up
            $plainPassword = rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '=');
        }

        $user = $this->loadUserByUsername($username);
        if (null === $user) {
            throw new \InvalidArgumentException(sprintf('The user "%s" does not exist.', $username));
        }
        $password = $this->encoder->encodePassword($user, $plainPassword);
        $user->setPassword($password);

        return $plainPassword;
    }

    /**
     * Add a role to a user
     *
     * @param string $username
     * @param string $role
     *
     * @throws \InvalidArgumentException
     */
    public function addRoleToUser(string $username, string $role): void
    {
        $user = $this->loadUserByUsername($username);
        if (null === $user) {
            throw new \InvalidArgumentException(sprintf('The user "%s" does not exist.', $username));
        }

        $user->addRole($role);
    }

    /**
     * Remove a role from a user
     *
     * @param string $username
     * @param string $role
     *
     * @throws \InvalidArgumentException
     */
    public function removeRoleFromUser(string $username, string $role): void
    {
        $user = $this->loadUserByUsername($username);
        if (null === $user) {
            throw new \InvalidArgumentException(sprintf('The user "%s" does not exist.', $username));
        }

        $user->removeRole($role);
    }
}
