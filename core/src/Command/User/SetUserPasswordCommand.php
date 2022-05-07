<?php

namespace App\Command\User;

use App\Command\BaseCommand;
use Symfony\Component\Validator\Constraints as Assert;

class SetUserPasswordCommand extends BaseCommand
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    private string $username;

    private ?string $plainPassword;

    public function __construct(string $username, ?string $plainPassword = null)
    {
        $this->username = $username;
        $this->plainPassword = $plainPassword;
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
