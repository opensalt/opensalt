<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class CopyFrameworkCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $fromDoc,
        private readonly LsDoc $toDoc,
        private readonly string $copyType = 'copy',
    ) {
    }

    public function getFromDoc(): LsDoc
    {
        return $this->fromDoc;
    }

    public function getToDoc(): LsDoc
    {
        return $this->toDoc;
    }

    public function getCopyType(): string
    {
        return $this->copyType;
    }
}
