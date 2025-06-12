<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class CopyDocumentToItemCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $fromDoc,
        private readonly LsDoc $toDoc,
        private readonly ?\Closure $callback = null,
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

    public function getCallback(): ?\Closure
    {
        return $this->callback;
    }
}
