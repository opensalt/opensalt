<?php

namespace Salt\UserBundle\Form\DTO;

use CftfBundle\Entity\LsDoc;
use Salt\UserBundle\Entity\User;

class AddAclUserDTO
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var LsDoc
     */
    public $lsDoc;

    /**
     * @var int
     */
    public $access;
}
