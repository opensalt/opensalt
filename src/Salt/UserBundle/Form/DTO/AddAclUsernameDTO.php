<?php

namespace Salt\UserBundle\Form\DTO;

use CftfBundle\Entity\LsDoc;

class AddAclUsernameDTO
{
    /**
     * @var string
     */
    public $username;

    /**
     * @var LsDoc
     */
    public $lsDoc;

    /**
     * @var int
     */
    public $access;
}
