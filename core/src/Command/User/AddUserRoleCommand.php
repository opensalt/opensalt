<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use Symfony\Component\Validator\Constraints as Assert;

class AddUserRoleCommand extends BaseCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $username,
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $role,
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
