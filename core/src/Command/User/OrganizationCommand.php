<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use App\Entity\User\Organization;
use Symfony\Component\Validator\Constraints as Assert;

abstract class OrganizationCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(Organization::class)]
        #[Assert\NotNull]
        private readonly Organization $organization,
    ) {
    }

    public function getOrg(): Organization
    {
        return $this->organization;
    }
}
