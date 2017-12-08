<?php

namespace App\Command\User;

use App\Command\BaseCommand;
use Salt\UserBundle\Entity\User;
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

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
