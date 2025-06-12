<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class DeriveDocumentCommand extends BaseCommand
{
    private LsDoc $derivedDoc;

    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $doc,
        private readonly string $fileContent,
        private readonly string $frameworkToAssociate,
    ) {
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    public function getFileContent(): string
    {
        return $this->fileContent;
    }

    public function getFrameworkToAssociate(): string
    {
        return $this->frameworkToAssociate;
    }

    public function getDerivedDoc(): LsDoc
    {
        return $this->derivedDoc;
    }

    public function setDerivedDoc(LsDoc $derivedDoc): void
    {
        $this->derivedDoc = $derivedDoc;
    }
}
