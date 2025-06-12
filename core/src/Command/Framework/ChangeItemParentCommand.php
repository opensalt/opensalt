<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Form\DTO\ChangeLsItemParentDTO;
use Symfony\Component\Validator\Constraints as Assert;

class ChangeItemParentCommand extends BaseCommand
{
    /**
     * Constructor.
     */
    public function __construct(
        #[Assert\Type(ChangeLsItemParentDTO::class)]
        #[Assert\NotNull]
        private readonly ChangeLsItemParentDTO $dto,
    ) {
    }

    public function getDto(): ChangeLsItemParentDTO
    {
        return $this->dto;
    }
}
