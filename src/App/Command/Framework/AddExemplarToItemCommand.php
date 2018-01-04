<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class AddExemplarToItemCommand extends BaseCommand
{
    /**
     * @var LsItem
     *
     * @Assert\Type(LsItem::class)
     * @Assert\NotNull()
     */
    private $item;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull()
     */
    private $url;

    /**
     * @var LsAssociation|null
     *
     * @Assert\Type(LsAssociation::class)
     */
    private $association;

    /**
     * Constructor.
     */
    public function __construct(LsItem $item, string $url, ?LsAssociation $association = null)
    {
        $this->item = $item;
        $this->url = $url;
        $this->association = $association;
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAssociation(): ?LsAssociation
    {
        return $this->association;
    }

    public function setAssociation(?LsAssociation $association): void
    {
        $this->association = $association;
    }
}
