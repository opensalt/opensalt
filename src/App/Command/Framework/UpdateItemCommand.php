<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;

class UpdateItemCommand extends BaseCommand
{
    /**
     * @var LsItem
     */
    private $item;

    public function __construct(LsItem $item)
    {
        $this->item = $item;
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getDoc(): LsDoc
    {
        return $this->item->getLsDoc();
    }
}
