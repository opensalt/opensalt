<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;

class UpdateItemCommand extends BaseCommand
{
    public function __construct(private readonly LsItem $item)
    {
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
