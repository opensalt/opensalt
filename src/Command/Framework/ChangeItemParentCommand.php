<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Form\DTO\ChangeLsItemParentDTO;
use Symfony\Component\Validator\Constraints as Assert;

class ChangeItemParentCommand extends BaseCommand
{
    /**
     * @var ChangeLsItemParentDTO
     *
     * @Assert\Type(ChangeLsItemParentDTO::class)
     * @Assert\NotNull()
     */
    private $dto;

    /**
     * Constructor.
     */
    public function __construct(ChangeLsItemParentDTO $dto)
    {
        $this->dto = $dto;
    }

    public function getDto(): ChangeLsItemParentDTO
    {
        return $this->dto;
    }
}
