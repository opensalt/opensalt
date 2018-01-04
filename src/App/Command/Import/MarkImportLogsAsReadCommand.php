<?php

namespace App\Command\Import;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDoc;

class MarkImportLogsAsReadCommand extends BaseCommand
{
    /**
     * @var LsDoc
     */
    private $doc;

    public function __construct(LsDoc $doc)
    {
        $this->doc = $doc;
    }

    /**
     * @return LsDoc
     */
    public function getDoc(): LsDoc
    {
        return $this->doc;
    }
}
