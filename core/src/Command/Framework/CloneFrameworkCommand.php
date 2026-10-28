<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class CloneFrameworkCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $doc,
        private readonly bool $associateWithOriginal = false,
        private readonly bool $copyAssociations = false,
    ) {
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    public function associateWithOriginal(): bool
    {
        return $this->associateWithOriginal;
    }

    public function copyAssociations(): bool
    {
        return $this->copyAssociations;
    }
}
