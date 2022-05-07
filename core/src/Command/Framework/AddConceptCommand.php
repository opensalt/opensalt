<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefConcept;
use Symfony\Component\Validator\Constraints as Assert;

class AddConceptCommand extends BaseCommand
{
    /**
     * @var LsDefConcept
     */
    #[Assert\Type(LsDefConcept::class)]
    #[Assert\NotNull]
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
