<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsItem;
use CftfBundle\Form\DTO\CopyToLsDocDTO;
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
     *
     * @param CopyToLsDocDTO $dto
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
