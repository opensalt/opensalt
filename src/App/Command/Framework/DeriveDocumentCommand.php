<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class DeriveDocumentCommand extends BaseCommand
{
    /**
     * @var LsDoc
     *
     * @Assert\Type(LsDoc::class)
     * @Assert\NotNull()
     */
    private $doc;

    /**
     * @var string
     */
    private $fileContent;

    /**
     * @var string
     */
    private $frameworkToAssociate;

    /**
     * @var LsDoc
     */
    private $derivedDoc;

    public function __construct(LsDoc $doc, string $fileContent, string $frameworkToAssociate)
    {
        $this->doc = $doc;
        $this->fileContent = $fileContent;
        $this->frameworkToAssociate = $frameworkToAssociate;
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
