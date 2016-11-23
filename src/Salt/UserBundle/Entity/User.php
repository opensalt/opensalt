<?php
/**
 *
 */

namespace Salt\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 *
 * @ORM\Entity(repositoryClass="Salt\UserBundle\Repository\UserRepository")
 * @ORM\Table(name="salt_user")
 */
class User implements UserInterface, \Serializable, EquatableInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(groups={"registration", "Default"})
     */
    protected $username;

    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"registration"})
     * @Assert\Length(max=4096)
     */
    private $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=64, nullable=true)
     */
    protected $password;

    /**
     * @ORM\Column(name="roles", type="json_array", nullable=true)
     */
    protected $roles;


    public function __construct($username = null) {
        if (!empty($username)) {
            $this->username = $username;
        }
    }

    /**
     * Returns the internal id of the user
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username) {
        $this->username = $username;

        return $this;
    }

    public function getPlainPassword() {
        return $this->plainPassword;
    }

    public function setPlainPassword($password) {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;

        return $this;
    }

    /**
     * Returns the roles granted to the user.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles() {
        $roles = $this->roles;

        if (empty($roles) || count($roles) === 0) {
            $roles = ['ROLE_USER'];
        }

        return $roles;
    }

    /**
     * @param string[] $roles The user roles
     */
    public function setRoles($roles) {
        $this->roles = array_values($roles);
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt() {
        return null;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials() {
    }

    /**
     * String representation of the user
     * @return string the string representation of the user
     */
    public function serialize() {
        return serialize([
            $this->id,
            $this->username,
        ]);
    }

    /**
     * Constructs the object
     * @param string $serialized The string representation of the object.
     * @return void
     */
    public function unserialize($serialized) {
        list(
            $this->id,
            $this->username,
        ) = unserialize($serialized);
    }

    /**
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether re-authentication is required.
     *
     * Also implementation should consider that $user instance may implement
     * the extended user interface `AdvancedUserInterface`.
     *
     * @param UserInterface $user
     *
     * @return bool
     */
    public function isEqualTo(UserInterface $user) {
        if (!($user instanceof User)) {
            var_dump('failed instanceof');exit();
            return false;
        }

        if ($user->getUsername() !== $this->getUsername()) {
            var_dump('failed username');exit();
            return false;
        }

        if ($user->getId() !== $this->getId()) {
            var_dump('failed id');exit();
            return false;
        }

        return true;
    }
}