<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDefItemType;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteItemTypeCommand extends BaseCommand
{
    /**
     * @var LsDefItemType
     *
     * @Assert\Type(LsDefItemType::class)
     * @Assert\NotNull()
     */
    private $itemType;

    public function __construct(LsDefItemType $itemType)
    {
        $this->itemType = $itemType;
    }

    public function getItemType(): LsDefItemType
    {
        return $this->itemType;
    }
}
