<?php

declare(strict_types=1);

namespace App\Command\Import;

use App\Command\BaseCommand;
use App\Entity\User\Organization;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class ImportCaseJsonCommand extends BaseCommand
{
    public function __construct(
        #[Assert\NotNull]
        private readonly string $caseJson,
        private readonly ?Organization $organization = null,
        private readonly ?User $user = null,
    ) {
    }

    public function getCaseJson(): string
    {
        return $this->caseJson;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
