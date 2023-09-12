<?php

namespace App\Command\User;

use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class AddUserCommand extends UserCommand
{
    /**
     * @var string
     */
    #[Assert\NotNull]
    #[Assert\Length(min: 8, max: 4096, minMessage: 'Password must be at least {{ limit }} characters long', maxMessage: 'Password cannot be longer than {{ limit }} characters')]
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
