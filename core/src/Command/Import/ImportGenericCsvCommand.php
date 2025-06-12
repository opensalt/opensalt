<?php

declare(strict_types=1);

namespace App\Command\Import;

use App\Command\BaseCommand;
use App\Entity\User\Organization;
use Symfony\Component\Validator\Constraints as Assert;

class ImportGenericCsvCommand extends BaseCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $filePath,
        private readonly ?string $creator = null,
        private readonly ?string $title = null,
        private readonly ?Organization $organization = null,
    ) {
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
