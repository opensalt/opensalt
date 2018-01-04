<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDefItemType;

class UpdateItemTypeCommand extends BaseCommand
{
    /**
     * @var LsDefItemType
     */
    private $itemType;

    public function __construct(LsDefItemType $itemType)
    {
        $this->itemType = $itemType;
    }

    public function getItemType(): LsDefItemType
    {
        return $this->itemType;
    }
}
