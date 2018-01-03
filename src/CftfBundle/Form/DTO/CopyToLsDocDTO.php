<?php

namespace CftfBundle\Form\DTO;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class CopyToLsDocDTO
{
    /**
     * @var LsDoc
     *
     * @Assert\Type(LsDoc::class)
     * @Assert\NotNull()
     */
    public $lsDoc;

    /**
     * @var LsItem
     *
     * @Assert\Type(LsItem::class)
     * @Assert\NotNull()
     */
    public $lsItem;
}
