<?php

namespace App\Command\Import;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;

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


    public function getDoc(): LsDoc
    {
        return $this->doc;
    }
}
