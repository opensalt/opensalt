<?php

namespace Salt\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @return UserInterface|null
     */
    public function loadUserByUsername($username) {
        $user = $this->findOneBy(['username'=>$username]);

        if ($user instanceof UserInterface) {
            return $user;
        }

        return null;
    }

    public function addNewUser($username, $plainPassword = null, $role = null) {
        if (is_null($plainPassword)) {
            // if there is no password, make something ugly up
            $plainPassword = rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '=');
        }

        if (is_null($role)) {
            $role = 'ROLE_USER';
        }

        $user = new User($username);
        $password = $this->encoder->encodePassword($user, $plainPassword);
        $user->setPassword($password);
        $user->addRole($role);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);
    }
}
