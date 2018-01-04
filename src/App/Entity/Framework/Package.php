<?php

namespace App\Entity\Framework;

use CftfBundle\Entity\AbstractLsBase;
use CftfBundle\Entity\CfRubric;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class Package extends AbstractLsBase
{
    /**
     * @var LsDoc
     * @Assert\Type(LsDoc::class)
     */
    private $doc;

    /**
     * @var Collection|LsItem[]
     * @Assert\All({
     *     @Assert\Type(LsItem::class)
     * })
     * @Assert\Valid(traverse=true)
     */
    private $items;

    /**
     * @var Collection|LsAssociation[]
     * @Assert\All({
     *     @Assert\Type(LsAssociation::class)
     * })
     * @Assert\Valid(traverse=true)
     */
    private $associations;

    /**
     * @var Collection|CfRubric[]
     * @Assert\All({
     *     @Assert\Type(CfRubric::class)
     * })
     * @Assert\Valid(traverse=true)
     */
    private $rubrics;

    public function __construct(LsDoc $doc, $identifier = null)
    {
        parent::__construct($identifier);
        $this->doc = $doc;
        $this->items = new ArrayCollection();
        $this->associations = new ArrayCollection();
        $this->rubrics = new ArrayCollection();
    }
}
