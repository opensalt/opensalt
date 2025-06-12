<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefConcept;

class UpdateConceptCommand extends BaseCommand
{
    public function __construct(private readonly LsDefConcept $concept)
    {
    }

    public function getConcept(): LsDefConcept
    {
        return $this->concept;
    }
}
