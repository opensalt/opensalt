<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsItem;
use App\Form\DTO\CopyToLsDocDTO;
use Symfony\Component\Validator\Constraints as Assert;

class CopyItemToDocCommand extends BaseCommand
{
    private LsItem $newItem;

    /**
     * Constructor.
     */
    public function __construct(
        #[Assert\Type(CopyToLsDocDTO::class)]
        #[Assert\NotNull]
        private readonly CopyToLsDocDTO $dto,
    ) {
    }

    public function getDto(): CopyToLsDocDTO
    {
        return $this->dto;
    }

    public function getNewItem(): LsItem
    {
        return $this->newItem;
    }

    public function setNewItem(LsItem $newItem): void
    {
        $this->newItem = $newItem;
    }
}
