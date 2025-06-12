<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class RemoveChildCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsItem::class)]
        #[Assert\NotNull]
        private readonly LsItem $parent,
        #[Assert\Type(LsItem::class)]
        #[Assert\NotNull]
        private readonly LsItem $child,
        private readonly ?\Closure $callback = null,
    ) {
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
