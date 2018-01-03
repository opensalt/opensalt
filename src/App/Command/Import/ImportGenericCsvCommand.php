<?php

namespace App\Command\Import;

use App\Command\BaseCommand;
use Salt\UserBundle\Entity\Organization;
use Symfony\Component\Validator\Constraints as Assert;

class ImportGenericCsvCommand extends BaseCommand
{
    /**
     * @var string
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     */
    private $filePath;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var string
     */
    private $creator;

    /**
     * @var null|string
     */
    private $title;

    public function __construct(string $filePath, ?string $creator = null, ?string $title = null, ?Organization $organization = null)
    {
        $this->filePath = $filePath;
        $this->creator = $creator;
        $this->title = $title;
        $this->organization = $organization;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
