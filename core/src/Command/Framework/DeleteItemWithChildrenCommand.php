<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteItemWithChildrenCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsItem::class)]
        #[Assert\NotNull]
        private readonly LsItem $item,
        private readonly ?\Closure $callback = null,
    ) {
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
