<?php

namespace App\Form\DTO;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class CopyToLsDocDTO
{
    /**
     * @var LsDoc
     */
    #[Assert\Type(LsDoc::class)]
    #[Assert\NotNull]
    public $lsDoc;

    /**
     * @var LsItem
     */
    #[Assert\Type(LsItem::class)]
    #[Assert\NotNull]
    public $lsItem;
}
