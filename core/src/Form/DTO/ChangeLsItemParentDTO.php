<?php

namespace App\Form\DTO;

use App\Entity\Framework\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class ChangeLsItemParentDTO
{
    /**
     * @var LsItem
     */
    #[Assert\Type(LsItem::class)]
    public $parentItem;

    /**
     * @var LsItem
     */
    #[Assert\Type(LsItem::class)]
    #[Assert\NotNull]
    public $lsItem;
}
