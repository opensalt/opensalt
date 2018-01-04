<?php

namespace Salt\UserBundle\Form\DTO;

use CftfBundle\Entity\LsDoc;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class AddAclUserDTO
{
    /**
     * @var User
     *
     * @Assert\Type(User::class)
     * @Assert\NotNull()
     */
    public $user;

    /**
     * @var LsDoc
     *
     * @Assert\Type(LsDoc::class)
     * @Assert\NotNull()
     */
    public $lsDoc;

    /**
     * @var int
     *
     * @Assert\Type("int")
     * @Assert\NotNull()
     */
    public $access;

    public function __construct(LsDoc $doc, int $access, User $user = null)
    {
        $this->lsDoc = $doc;
        $this->access = $access;
        $this->user = $user;
    }
}
