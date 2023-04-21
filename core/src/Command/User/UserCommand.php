<?php

namespace App\Command\User;

use App\Command\BaseCommand;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

abstract class UserCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(User::class)]
        #[Assert\NotNull]
        private readonly User $user,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
