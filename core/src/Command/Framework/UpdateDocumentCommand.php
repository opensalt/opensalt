<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateDocumentCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $doc,
    ) {
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }
}
