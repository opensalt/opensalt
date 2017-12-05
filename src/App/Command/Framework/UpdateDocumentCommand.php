<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDoc;

class UpdateDocumentCommand extends BaseCommand
{
    /**
     * @var LsDoc
     *
     * @Assert\Type(LsDoc::class)
     * @Assert\NotNull()
     */
    private $doc;

    /**
     * UpdateDocumentCommand constructor.
     * @param LsDoc $lsDoc
     */
    public function __construct(LsDoc $doc)
    {
        $this->doc = $doc;
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }
}
