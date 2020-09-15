<?php

namespace App\Command\User;

use App\Command\BaseCommand;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

abstract class UserCommand extends BaseCommand
{
    /**
     * @var User
     *
     * @Assert\Type(User::class)
     * @Assert\NotNull()
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }


    public function getUser(): User
    {
        return $this->user;
    }
}
