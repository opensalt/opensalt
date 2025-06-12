<?php

declare(strict_types=1);

namespace App\Command\Import;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;

class MarkImportLogsAsReadCommand extends BaseCommand
{
    public function __construct(private readonly LsDoc $doc)
    {
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }
}
