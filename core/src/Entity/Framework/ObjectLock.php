<?php

namespace App\Entity\Framework;

use App\Entity\LockableInterface;
use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected User $user;

    /**
     * @ORM\Column(name="expiry", type="datetime", precision=6, nullable=false)
     */
    protected \DateTime $timeout;

    /**
     * @ORM\Column(name="obj_type", type="string", nullable=false)
     */
    protected string $objectType;

    /**
     * @ORM\Column(name="obj_id", type="string", nullable=false)
     */
    protected string $objectId;

    /**
     * @ORM\ManyToOne(targetEntity="LsDoc")
     */
    protected ?LsDoc $doc;

    public function __construct(LockableInterface $obj, User $user, int $minutes = 5)
    {
        if (null === $obj->getId()) {
            throw new \RuntimeException('Attempt to lock non-persisted object.');
        }

        $this->user = $user;
        $this->timeout = new \DateTime("now + {$minutes} minutes");
        $this->objectType = \get_class($obj);
        $this->objectId = (string) $obj->getId();
        if ($obj instanceof LsDoc) {
            $this->doc = $obj;
        } elseif (\method_exists($obj, 'getLsDoc')) {
            $this->doc = $obj->getLsDoc();
        }
    }

    public function getId(): ?int
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
