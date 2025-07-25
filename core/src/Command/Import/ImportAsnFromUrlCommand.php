<?php

declare(strict_types=1);

namespace App\Command\Import;

use App\Command\BaseCommand;
use App\Entity\User\AccessGroup;
use Symfony\Component\Validator\Constraints as Assert;

class ImportAsnFromUrlCommand extends BaseCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $asnIdOrUrl,
        private readonly ?string $creator = null,
        private readonly ?AccessGroup $organization = null,
    ) {
    }

    public function getAsnIdOrUrl(): string
    {
        return $this->asnIdOrUrl;
    }

    public function getOrganization(): ?AccessGroup
    {
        return $this->organization;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }
}
