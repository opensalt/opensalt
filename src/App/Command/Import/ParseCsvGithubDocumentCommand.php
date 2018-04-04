<?php

namespace App\Command\Import;

use App\Command\BaseCommand;

class ParseCsvGithubDocumentCommand extends BaseCommand
{
    /**
     * @var array
     */
    private $itemKeys;

    /**
     * @var string
     */
    private $fileContent;

    /**
     * @var string
     */
    private $docId;

    /**
     * @var string
     */
    private $frameworkToAssociate;

    /**
     * @var array
     */
    private $missingFieldsLog;

    public function __construct(array $lsItemKeys, string $fileContent, string $lsDocId, string $frameworkToAssociate, array $missingFieldsLog)
    {
        $this->itemKeys = $lsItemKeys;
        $this->fileContent = $fileContent;
        $this->docId = $lsDocId;
        $this->frameworkToAssociate = $frameworkToAssociate;
        $this->missingFieldsLog = $missingFieldsLog;
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
