<?php

namespace App\Entity\Framework;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Package extends AbstractLsBase
{
    /**
     * @Assert\Type(LsDoc::class)
     */
    private LsDoc $doc;

    /**
     * @var Collection|LsItem[]
     * @Assert\All({
     *     @Assert\Type(LsItem::class)
     * })
     * @Assert\Valid(traverse=true)
     */
    private Collection $items;

    /**
     * @var Collection|LsAssociation[]
     * @Assert\All({
     *     @Assert\Type(LsAssociation::class)
     * })
     * @Assert\Valid(traverse=true)
     */
    private Collection $associations;

    /**
     * @var Collection|CfRubric[]
     * @Assert\All({
     *     @Assert\Type(CfRubric::class)
     * })
     * @Assert\Valid(traverse=true)
     */
    private Collection $rubrics;

    /**
     * @param string|UuidInterface|null $identifier
     */
    public function __construct(LsDoc $doc, $identifier = null)
    {
        parent::__construct($identifier);
        $this->doc = $doc;
        $this->items = new ArrayCollection();
        $this->associations = new ArrayCollection();
        $this->rubrics = new ArrayCollection();
    }
}
