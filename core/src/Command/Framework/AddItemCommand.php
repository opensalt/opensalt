<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;

class AddItemCommand extends BaseCommand
{
    public function __construct(private readonly LsItem $item, private readonly LsDoc $doc, private readonly ?LsItem $parent = null, private readonly ?LsDefAssociationGrouping $assocGroup = null)
    {
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    public function getParent(): ?LsItem
    {
        return $this->parent;
    }

    public function getAssocGroup(): ?LsDefAssociationGrouping
    {
        return $this->assocGroup;
    }
}
