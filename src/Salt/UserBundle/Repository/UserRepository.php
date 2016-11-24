<?php

namespace Salt\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * UserRepository
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
    public function setEncoder(UserPasswordEncoderInterface $encoder) {
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
    public function loadUserByUsername($username) {
        $user = $this->findOneBy(['username'=>$username]);

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * Creates a user
     *
     * @param string $username
     * @param string|null $plainPassword
     * @param string|null $role
     *
     * @return string The user's password
     */
    public function addNewUser($username, $plainPassword = null, $role = null) {
        if (empty(trim($plainPassword))) {
            // if there is no password, make something ugly up
            $plainPassword = rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '=');
        }

        if (null === $role) {
            $role = 'ROLE_USER';
        }

        $user = new User($username);
        $password = $this->encoder->encodePassword($user, $plainPassword);
        $user->setPassword($password);
        $user->addRole($role);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);

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
    public function setUserPassword($username, $plainPassword = null) {
        if (empty(trim($plainPassword))) {
            // if there is no password, make something ugly up
            $plainPassword = rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '=');
        }

        $user = $this->loadUserByUsername($username);
        $password = $this->encoder->encodePassword($user, $plainPassword);
        $user->setPassword($password);

        $this->getEntityManager()->flush($user);

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
    public function addRoleToUser($username, $role) {
        $user = $this->loadUserByUsername($username);
        if (null === $user) {
            throw new \InvalidArgumentException(sprintf('The user "%s" does not exist.', $username));
        }

        $user->addRole($role);

        $this->getEntityManager()->flush($user);
    }

    /**
     * Remove a role from a user
     *
     * @param string $username
     * @param string $role
     *
     * @throws \InvalidArgumentException
     */
    public function removeRoleFromUser($username, $role) {
        $user = $this->loadUserByUsername($username);
        if (null === $user) {
            throw new \InvalidArgumentException(sprintf('The user "%s" does not exist.', $username));
        }

        $user->removeRole($role);

        $this->getEntityManager()->flush($user);
    }
}
