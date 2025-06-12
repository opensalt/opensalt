<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use App\Entity\User\Organization;
use Symfony\Component\Validator\Constraints as Assert;

class AddUserByNameCommand extends BaseCommand
{
    private string $newPassword;

    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $userName,
        #[Assert\NotNull]
        private readonly Organization $organization,
        private readonly ?string $plainPassword = null,
        private readonly ?string $role = null,
    ) {
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $newPassword): void
    {
        $this->newPassword = $newPassword;
    }
}
