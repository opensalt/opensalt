<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\AwsStorage;
use App\Entity\Framework\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class AddFileToAwsCommand extends BaseCommand
{
    /**
     * @var LsItem
     */
    private $item;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull()
     */

    private $fileName;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull()
     */

    private $field;

    public function __construct(LsItem $item, string $fileName)
    {
        $this->item = $item;
        $this->fileName = $fileName;
        $this->field = $field;
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
