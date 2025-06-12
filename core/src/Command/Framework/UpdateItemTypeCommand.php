<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefItemType;

class UpdateItemTypeCommand extends BaseCommand
{
    public function __construct(private readonly LsDefItemType $itemType)
    {
    }

    public function getItemType(): LsDefItemType
    {
        return $this->itemType;
    }
}
