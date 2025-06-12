<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteDocumentCommand extends BaseCommand
{
    /**
     * constructor.
     */
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $doc,
        private readonly ?\Closure $callback = null,
    ) {
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    public function getProgressCallback(): ?\Closure
    {
        return $this->callback;
    }
}
