<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateFrameworkCommand extends BaseCommand
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
     * @var array
     */
    private $cfItemKeys;

    public function __construct(LsDoc $doc, string $fileContent, string $frameworkToAssociate, array $cfItemKeys)
    {
        $this->doc = $doc;
        $this->fileContent = $fileContent;
        $this->frameworkToAssociate = $frameworkToAssociate;
        $this->cfItemKeys = $cfItemKeys;
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

    public function getCfItemKeys(): array
    {
        return $this->cfItemKeys;
    }
}
