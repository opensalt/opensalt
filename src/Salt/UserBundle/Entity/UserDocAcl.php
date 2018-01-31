<?php

namespace Salt\UserBundle\Entity;

use CftfBundle\Entity\LsDoc;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class UserDocAcl
 *
 * @ORM\Entity(repositoryClass="Salt\UserBundle\Repository\UserDocAclRepository")
 * @ORM\Table(
 *     name="salt_user_doc_acl",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uniq_acl_id", columns={"doc_id", "user_id"})
 *     }
 * )
 */
class UserDocAcl
{
    public const DENY = 0;
    public const ALLOW = 1;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="docAcls", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc", inversedBy="docAcls", fetch="EAGER")
     * @ORM\JoinColumn(name="doc_id", referencedColumnName="id", nullable=false)
     */
    protected $lsDoc;

    /**
     * @var int 0|1 indicating Deny or Allow
     *
     * @ORM\Column(name="access", type="smallint", nullable=false)
     */
    protected $access;


    /**
     * UserDocAcl constructor.
     *
     * @param User $user
     * @param LsDoc $lsDoc
     * @param int $access
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(User $user, LsDoc $lsDoc, int $access)
    {
        if (!in_array($access, [self::DENY, self::ALLOW], true)) {
            throw new \InvalidArgumentException('Invalid value for "access".  Access can only be 0 or 1');
        }
        $this->user = $user;
        $this->lsDoc = $lsDoc;
        $this->access = $access;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * The User that the ACL is for
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * The Document that the ACL is for
     *
     * @return LsDoc
     */
    public function getLsDoc(): LsDoc
    {
        return $this->lsDoc;
    }

    /**
     * Determine if the user has access
     *
     * @return int 0|1 indicating Deny or Allow
     */
    public function getAccess(): int
    {
        return $this->access;
    }
}
