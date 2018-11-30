<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\DTO\LsItemAdditionalFieldFormObject;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;

class AddItemCommand extends BaseCommand
{
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
    /**
     * @var LsItemAdditionalFieldFormObject
     */
    private $additionalFieldFormObject;

    public function __construct(LsItemAdditionalFieldFormObject $additionalFieldFormObject, LsDoc $doc, ?LsItem $parent = null, ?LsDefAssociationGrouping $assocGroup = null)
    {
        $this->doc = $doc;
        $this->parent = $parent;
        $this->assocGroup = $assocGroup;
        $this->additionalFieldFormObject = $additionalFieldFormObject;
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

    /**
     * @return LsItemAdditionalFieldFormObject
     */
    public function getAdditionalFieldFormObject(): LsItemAdditionalFieldFormObject
    {
        return $this->additionalFieldFormObject;
    }

}
