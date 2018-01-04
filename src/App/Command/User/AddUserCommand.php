<?php

namespace App\Command\User;

use Salt\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class AddUserCommand extends UserCommand
{
    /**
     * @var string
     *
     * @Assert\NotNull()
     * @Assert\Length(
     *     min = 6,
     *     minMessage="The new password should be at least 6 characters long"
     * )
     */
    private $encryptedPassword;

    public function __construct(User $user, string $encryptedPassword)
    {
        parent::__construct($user);
        $this->encryptedPassword = $encryptedPassword;
    }

    public function getEncryptedPassword(): string
    {
        return $this->encryptedPassword;
    }
}
