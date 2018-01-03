<?php

namespace App\Command\Import;

use App\Command\BaseCommand;
use Salt\UserBundle\Entity\Organization;
use Symfony\Component\Validator\Constraints as Assert;

class ImportExcelFileCommand extends BaseCommand
{
    /**
     * @var string
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     */
    private $excelFilePath;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var string
     */
    private $creator;

    public function __construct(string $excelFilePath, ?string $creator = null, ?Organization $organization = null)
    {
        $this->excelFilePath = $excelFilePath;
        $this->organization = $organization;
        $this->creator = $creator;
    }

    public function getExcelFilePath(): string
    {
        return $this->excelFilePath;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }
}
