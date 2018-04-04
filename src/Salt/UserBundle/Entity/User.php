<?php

namespace Salt\UserBundle\Entity;

use CftfBundle\Entity\LsDoc;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 *
 * @ORM\Entity(repositoryClass="Salt\UserBundle\Repository\UserRepository")
 * @ORM\Table(name="salt_user")
 * @UniqueEntity("username")
 */
class User implements AdvancedUserInterface, \Serializable, EquatableInterface
{
    public const USER_ROLES = [
        'ROLE_EDITOR',
        'ROLE_ADMIN',
        'ROLE_SUPER_EDITOR',
        'ROLE_SUPER_USER',
    ];

    public const ACTIVE = 0;
    public const SUSPENDED = 1;
    public const PENDING = 2;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Salt\UserBundle\Entity\Organization", inversedBy="users")
     * @ORM\JoinColumn(name="org_id", referencedColumnName="id", nullable=false)
     *
     * @Assert\NotBlank()
     */
    protected $org;

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
     * @var string[]
     *
     * @ORM\Column(name="roles", type="json_array", nullable=true)
     */
    protected $roles = [];

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default": User::PENDING})
     */
    protected $status = self::PENDING;

    /**
     * @ORM\Column(name="github_token", type="string", length=40, nullable=true)
     */
    protected $githubToken;

    /**
     * @var LsDoc[]|Collection
     * @ORM\OneToMany(targetEntity="CftfBundle\Entity\LsDoc", mappedBy="user", indexBy="id", fetch="EXTRA_LAZY")
     */
    protected $frameworks;

    /**
     * @var UserDocAcl[]|Collection
     * @ORM\OneToMany(targetEntity="UserDocAcl", mappedBy="user", indexBy="lsDoc", fetch="EXTRA_LAZY")
     */
    protected $docAcls;


    public function __construct($username = null) {
        if (!empty($username)) {
            $this->username = $username;
        }

        $this->frameworks = new ArrayCollection();
    }

    public static function getUserRoles() {
        return static::USER_ROLES;
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
     *
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
     * @return string[] The user roles
     */
    public function getRoles() {
        $roles = $this->roles;

        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return $roles;
    }

    /**
     * @param string[] $roles The user roles
     */
    public function setRoles($roles) {
        if (!$roles instanceof \Traversable) {
            throw new \InvalidArgumentException('The passed roles are not an array');
        }

        $this->roles = [];
        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    public function getGithubToken(){
        return $this->githubToken;
    }

    public function setGithubToken($token) {
        $this->githubToken = $token;

        return $this;
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
     *
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
     *
     * @param string $serialized the string representation of the object
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
        if (!($user instanceof self)) {
            return false;
        }

        if ($user->getUsername() !== $this->getUsername()) {
            return false;
        }

        if ($user->getId() !== $this->getId()) {
            return false;
        }

        return true;
    }

    /**
     * Add a role to a user
     *
     * @param string $role
     *
     * @return $this
     */
    public function addRole($role) {
        if ('ROLE_USER' === $role) {
            return $this;
        }

        if (!in_array($role, static::USER_ROLES, true)) {
            throw new \InvalidArgumentException(sprintf('The role "%s" is not valid', $role));
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole($role) {
        if (($key = array_search($role, $this->roles, true)) !== false) {
            unset($this->roles[$key]);
        }

        return $this;
    }

    /**
     * @return \Salt\UserBundle\Entity\Organization
     */
    public function getOrg() {
        return $this->org;
    }

    /**
     * @param \Salt\UserBundle\Entity\Organization $org
     *
     * @return User
     */
    public function setOrg($org) {
        $this->org = $org;

        return $this;
    }

    /**
     * Get the frameworks owned by the user
     *
     * @return \CftfBundle\Entity\LsDoc[]|\Doctrine\Common\Collections\Collection
     */
    public function getFrameworks() {
        return $this->frameworks;
    }

    /**
     * @return Collection|UserDocAcl[]
     */
    public function getDocAcls() {
        return $this->docAcls;
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired() {
        // Accounts do not currently expire
        return true;
    }

    /**
     * Checks whether the user is suspended.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not suspended, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked() {
        return !($this->isSuspended() || $this->isPending());
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired() {
        // Currently credentials do not expire
        return true;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled() {
        return true;
    }

    /**
     * @return bool true if the user is suspended
     */
    public function isSuspended() {
        if ($this->status === static::SUSPENDED) {
            return true;
        }

        return false;
    }

    /**
     * Suspend the user
     *
     * @return $this
     */
    public function suspendUser() {
        $this->status = static::SUSPENDED;

        return $this;
    }

    /**
     * Activate the user
     *
     * @return $this
     */
    public function activateUser() {
        $this->status = static::ACTIVE;

        return $this;
    }

    /**
     * @return bool true if the user is pending for approval for approval
     */
    public function isPending() {
        if (static::PENDING === $this->status) {
            return true;
        }

        return false;
    }
}
