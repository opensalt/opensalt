<?php

namespace CftfBundle\Form\DTO;

use CftfBundle\Entity\LsItem;

class ChangeLsItemParentDTO
{
    /**
     * @var LsItem
     */
    public $parentItem;

    /**
     * @var LsItem
     */
    public $lsItem;
}
