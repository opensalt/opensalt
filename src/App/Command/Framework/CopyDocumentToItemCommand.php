<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class CopyDocumentToItemCommand extends BaseCommand
{
    /**
     * @var LsDoc
     *
     * @Assert\Type(LsDoc::class)
     * @Assert\NotNull()
     */
    private $fromDoc;

    /**
     * @var LsDoc
     */
    private $toDoc;

    /**
     * @var \Closure|null
     */
    private $callback;

    public function __construct(LsDoc $fromDoc, LsDoc $toDoc, ?\Closure $callback = null)
    {
        $this->fromDoc = $fromDoc;
        $this->toDoc = $toDoc;
        $this->callback = $callback;
    }

    public function getFromDoc(): LsDoc
    {
        return $this->fromDoc;
    }

    public function getToDoc(): LsDoc
    {
        return $this->toDoc;
    }

    public function getCallback(): ?\Closure
    {
        return $this->callback;
    }
}
