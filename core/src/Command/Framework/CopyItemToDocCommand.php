<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsItem;
use App\Form\DTO\CopyToLsDocDTO;
use Symfony\Component\Validator\Constraints as Assert;

class CopyItemToDocCommand extends BaseCommand
{
    /**
     * @var CopyToLsDocDTO
     *
     * @Assert\Type(CopyToLsDocDTO::class)
     * @Assert\NotNull()
     */
    private $dto;

    /**
     * @var LsItem
     */
    private $newItem;

    /**
     * Constructor.
     */
    public function __construct(CopyToLsDocDTO $dto)
    {
        $this->dto = $dto;
    }

    public function getDto(): CopyToLsDocDTO
    {
        return $this->dto;
    }

    public function getNewItem(): LsItem
    {
        return $this->newItem;
    }

    public function setNewItem(LsItem $newItem): LsItem
    {
        $this->newItem = $newItem;
    }
}
