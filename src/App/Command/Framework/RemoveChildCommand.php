<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class RemoveChildCommand extends BaseCommand
{
    /**
     * @var LsItem
     *
     * @Assert\Type(LsItem::class)
     * @Assert\NotNull()
     */
    private $parent;

    /**
     * @var LsItem
     *
     * @Assert\Type(LsItem::class)
     * @Assert\NotNull()
     */
    private $child;

    /**
     * @var \Closure|null
     */
    private $callback;

    public function __construct(LsItem $parent, LsItem $child, ?\Closure $progressCallback = null)
    {
        $this->parent = $parent;
        $this->callback = $progressCallback;
        $this->child = $child;
    }

    public function getParent(): LsItem
    {
        return $this->parent;
    }

    public function getProgressCallback(): ?\Closure
    {
        return $this->callback;
    }

    public function getChild(): LsItem
    {
        return $this->child;
    }
}
