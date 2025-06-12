<?php

declare(strict_types=1);

namespace App\Command\Import;

use App\Command\BaseCommand;

class ParseCsvGithubDocumentCommand extends BaseCommand
{
    public function __construct(
        private readonly array $itemKeys,
        private readonly string $fileContent,
        private readonly string $docId,
        private readonly string $frameworkToAssociate,
        private readonly array $missingFieldsLog,
    ) {
    }

    public function getItemKeys(): array
    {
        return $this->itemKeys;
    }

    public function getFileContent(): string
    {
        return $this->fileContent;
    }

    public function getDocId(): string
    {
        return $this->docId;
    }

    public function getFrameworkToAssociate(): string
    {
        return $this->frameworkToAssociate;
    }

    public function getMissingFieldsLog(): array
    {
        return $this->missingFieldsLog;
    }
}
