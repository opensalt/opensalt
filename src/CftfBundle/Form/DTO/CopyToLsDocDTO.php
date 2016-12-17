<?php
/**
 *
 */

namespace CftfBundle\Form\DTO;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;

class CopyToLsDocDTO
{
    /**
     * @var LsDoc
     */
    public $lsDoc;

    /**
     * @var LsItem
     */
    public $lsItem;
}