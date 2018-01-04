<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteItemWithChildrenCommand extends BaseCommand
{
    /**
     * @var LsItem
     *
     * @Assert\Type(LsItem::class)
     * @Assert\NotNull()
     */
    private $item;

    /**
     * @var \Closure|null
     */
    private $callback;

    public function __construct(LsItem $item, ?\Closure $progressCallback = null)
    {
        $this->item = $item;
        $this->callback = $progressCallback;
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getProgressCallback(): ?\Closure
    {
        return $this->callback;
    }
}
