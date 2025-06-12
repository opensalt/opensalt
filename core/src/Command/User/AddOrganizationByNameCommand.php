<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use Symfony\Component\Validator\Constraints as Assert;

class AddOrganizationByNameCommand extends BaseCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $organizationName,
    ) {
    }

    public function getOrganizationName(): string
    {
        return $this->organizationName;
    }
}
