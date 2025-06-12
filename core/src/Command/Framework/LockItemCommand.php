<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class LockItemCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsItem::class)]
        #[Assert\NotNull]
        private readonly LsItem $item,
        private readonly User $user,
    ) {
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
