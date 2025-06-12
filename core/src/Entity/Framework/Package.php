<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Package extends AbstractLsBase
{
    /**
     * @var Collection<array-key, LsItem>
     */
    #[Assert\All([new Assert\Type(LsItem::class)])]
    #[Assert\Valid]
    private readonly Collection $items;

    /**
     * @var Collection<array-key, LsAssociation>
     */
    #[Assert\All([new Assert\Type(LsAssociation::class)])]
    #[Assert\Valid]
    private readonly Collection $associations;

    /**
     * @var Collection<array-key, CfRubric>
     */
    #[Assert\All([new Assert\Type(CfRubric::class)])]
    #[Assert\Valid]
    private Collection $rubrics;

    public function __construct(
        #[Assert\Type(LsDoc::class)]
        private LsDoc $doc,
        UuidInterface|string|null $identifier = null,
    ) {
        parent::__construct($identifier);
        $this->items = new ArrayCollection();
        $this->associations = new ArrayCollection();
        $this->rubrics = new ArrayCollection();
    }
}
