<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Salt\UserBundle\Entity\User;

/**
 * @ORM\Table(name="salt_object_lock")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\ObjectLockRepository")
 */
class ObjectLock
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Salt\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiry", type="datetime", precision=6, nullable=false)
     */
    protected $timeout;

    /**
     * @var string
     *
     * @ORM\Column(name="locked_obj", nullable=false, unique=true)
     */
    protected $lock;

    public function __construct(string $object, string $id, User $user, int $minutes = 5)
    {
        $this->user = $user;
        $this->lock = "{$object}:{$id}";
        $this->timeout = new \DateTime("now + {$minutes} minutes");
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTimeout(): \DateTime
    {
        return $this->timeout;
    }

    public function getLock(): string
    {
        return $this->lock;
    }

    public function addTime(int $minutes): void
    {
        $this->timeout = new \DateTime("now + {$minutes} minutes");
    }
}
