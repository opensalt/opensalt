<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use Symfony\Component\Validator\Constraints as Assert;

class SetUserPasswordCommand extends BaseCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $username,
        private ?string $plainPassword = null,
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }
}
