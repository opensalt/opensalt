<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefItemType;
use Symfony\Component\Validator\Constraints as Assert;

class AddItemTypeCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDefItemType::class)]
        #[Assert\NotNull]
        private readonly LsDefItemType $itemType,
    ) {
    }

    public function getItemType(): LsDefItemType
    {
        return $this->itemType;
    }
}
