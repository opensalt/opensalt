<?php

declare(strict_types=1);

namespace App\Command\Import;

use App\Command\BaseCommand;
use App\Entity\User\Organization;
use Symfony\Component\Validator\Constraints as Assert;

class ImportExcelFileCommand extends BaseCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $excelFilePath,
        private readonly ?string $creator = null,
        private readonly ?Organization $organization = null,
    ) {
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
