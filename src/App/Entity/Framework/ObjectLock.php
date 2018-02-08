<?php

namespace App\Entity\Framework;

use App\Entity\LockableInterface;
use CftfBundle\Entity\LsDoc;
use Doctrine\ORM\Mapping as ORM;
use Salt\UserBundle\Entity\User;

/**
 * @ORM\Table(
 *     name="salt_object_lock",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="lock_obj_idx", columns={"obj_type", "obj_id"})
 *     },
 *     indexes={
 *         @ORM\Index(name="expiry_idx", columns={"expiry"})
 *     }
 * )
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
     * @ORM\Column(name="obj_type", type="string", nullable=false)
     */
    protected $objectType;

    /**
     * @var string
     *
     * @ORM\Column(name="obj_id", type="string", nullable=false)
     */
    protected $objectId;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc")
     */
    protected $doc;

    public function __construct(LockableInterface $obj, User $user, int $minutes = 5)
    {
        if (null === $obj->getId()) {
            throw new \RuntimeException('Attempt to lock non-persisted object.');
        }

        $this->user = $user;
        $this->timeout = new \DateTime("now + {$minutes} minutes");
        $this->objectType = \get_class($obj);
        $this->objectId = $obj->getId();
        if ($obj instanceof LsDoc) {
            $this->doc = $obj;
        } elseif (\method_exists($obj, 'getLsDoc')) {
            $this->doc = $obj->getLsDoc();
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isExpired(): bool
    {
        return new \DateTime() > $this->timeout;
    }

    public function getTimeout(): \DateTime
    {
        return $this->timeout;
    }

    public function addTime(int $minutes): void
    {
        $this->timeout = new \DateTime("now + {$minutes} minutes");
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getObjectId(): string
    {
        return $this->objectId;
    }
}
