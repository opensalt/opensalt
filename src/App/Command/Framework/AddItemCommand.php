<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;

class AddItemCommand extends BaseCommand
{
    /**
     * @var LsItem
     */
    private $item;

    /**
     * @var LsDoc
     */
    private $doc;

    /**
     * @var LsItem
     */
    private $parent;

    /**
     * @var LsDefAssociationGrouping
     */
    private $assocGroup;

    public function __construct(LsItem $item, LsDoc $doc, ?LsItem $parent = null, ?LsDefAssociationGrouping $assocGroup = null)
    {
        $this->item = $item;
        $this->doc = $doc;
        $this->parent = $parent;
        $this->assocGroup = $assocGroup;
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
