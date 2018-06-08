<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\AwsStorage;
use App\Entity\Framework\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateAttachmentCommand extends BaseCommand
{
    /**
     * @var LsItem
     */
    private $item;

    /**
     * @var array
     *
     * @Assert\Type("array")
     * @Assert\NotNull()
     */

    private $fileName;


    public function __construct(LsItem $item, array $fileName)
    {
        $this->item = $item;
        $this->fileName = $fileName;
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getFileName(): array
    {
        return $this->fileName;
    }

}
