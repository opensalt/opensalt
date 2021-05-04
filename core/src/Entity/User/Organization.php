<?php

namespace App\Entity\User;

use App\Entity\Framework\LsDoc;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Organization
 *
 * @ORM\Entity(repositoryClass="App\Repository\User\OrganizationRepository")
 * @ORM\Table(name="salt_org")
 * @UniqueEntity("name", message="The organization name is already being used")
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
     * @ORM\OneToMany(targetEntity="App\Entity\User\User", mappedBy="org", indexBy="id", fetch="EXTRA_LAZY")
     */
    private $users;

    /**
     * @var LsDoc[]|Collection
     * @ORM\OneToMany(targetEntity="App\Entity\Framework\LsDoc", mappedBy="org", indexBy="id", fetch="EXTRA_LAZY")
     */
    protected $frameworks;


    /**
     * Organization constructor.
     */
    public function __construct() {
        $this->users = new ArrayCollection();
        $this->frameworks = new ArrayCollection();
    }

    /**
     * Returns the internal id of the user
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }


    public function setName(string $name): Organization {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name of the organization
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|\App\Entity\User\User[]
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * Add a user to the organization
     *
     * @param \App\Entity\User\User $user
     */
    public function addUser(User $user): Organization {
        $this->users->add($user);

        return $this;
    }

    /**
     * Remove a user from the organization
     *
     * @param \App\Entity\User\User $user
     */
    public function removeUser(User $user): Organization {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * Get the list of frameworks owned by the organization
     *
     * @return \App\Entity\Framework\LsDoc[]|\Doctrine\Common\Collections\Collection
     */
    public function getFrameworks() {
        return $this->frameworks;
    }
}
