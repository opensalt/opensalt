<?php
/**
 *
 */

namespace Salt\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Organization
 *
 * @ORM\Entity(repositoryClass="Salt\UserBundle\Repository\OrganizationRepository")
 * @ORM\Table(name="salt_org")
 * @UniqueEntity("name")
 */
class Organization
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(groups={"registration", "Default"})
     */
    protected $name;

    /**
     * @var Collection|User[]
     *
     * @ORM\OneToMany(targetEntity="Salt\UserBundle\Entity\User", mappedBy="org", indexBy="id", fetch="EXTRA_LAZY")
     */
    private $users;

    /**
     * Organization constructor.
     */
    public function __construct() {
        $this->users = new ArrayCollection();
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
     * @param string $name
     * @return Organization
     */
    public function setName(string $name): Organization {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|\Salt\UserBundle\Entity\User[]
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection|\Salt\UserBundle\Entity\User[] $users
     * @return Organization
     */
    public function setUsers($users): Organization {
        $this->users = $users;

        return $this;
    }

    /**
     * @param \Salt\UserBundle\Entity\User $user
     * @return Organization
     */
    public function  addUser(User $user): Organization {
        $this->users->add($user);

        return $this;
    }

    /**
     * @param \Salt\UserBundle\Entity\User $user
     * @return Organization
     */
    public function removeUser(User $user): Organization {
        $this->users->removeElement($user);

        return $this;
    }
}
