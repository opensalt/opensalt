<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefConcept;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteConceptCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDefConcept::class)]
        #[Assert\NotNull]
        private readonly LsDefConcept $concept,
    ) {
    }

    public function getConcept(): LsDefConcept
    {
        return $this->concept;
    }
}
