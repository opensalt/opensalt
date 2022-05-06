<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class CopyFrameworkCommand extends BaseCommand
{
    /**
     * @var LsDoc
     */
    #[Assert\Type(LsDoc::class)]
    #[Assert\NotNull]
    private $fromDoc;

    /**
     * @var LsDoc
     */
    private $toDoc;

    /**
     * @var string
     */
    private $copyType;

    public function __construct(LsDoc $fromDoc, LsDoc $toDoc, string $copyType = 'copy')
    {
        $this->fromDoc = $fromDoc;
        $this->toDoc = $toDoc;
        $this->copyType = $copyType;
    }

    public function getFromDoc(): LsDoc
    {
        return $this->fromDoc;
    }

    public function getToDoc(): LsDoc
    {
        return $this->toDoc;
    }

    public function getCopyType(): string
    {
        return $this->copyType;
    }
}
