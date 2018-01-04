<?php

namespace Salt\UserBundle\Form\DTO;

use CftfBundle\Entity\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class AddAclUsernameDTO
{
    /**
     * @var string
     */
    public $username;

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

    public function __construct(LsDoc $doc, int $access, ?string $username = null)
    {
        $this->lsDoc = $doc;
        $this->access = $access;
        $this->username = $username;
    }
}
