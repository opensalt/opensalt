<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefConcept;

class UpdateConceptCommand extends BaseCommand
{
    /**
     * @var LsDefConcept
     */
    private $concept;

    public function __construct(LsDefConcept $concept)
    {
        $this->concept = $concept;
    }

    public function getConcept(): LsDefConcept
    {
        return $this->concept;
    }
}
