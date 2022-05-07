<?php

namespace App\Form\DTO;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordDTO
{
    /**
     * @var string
     *
     * @SecurityAssert\UserPassword(message="The current password is incorrect")
     */
    public $oldPassword;

    /**
     * @var string
     */
    #[Assert\Length(min: 6, minMessage: 'The new password should be at least 6 characters long')]
    public $newPassword;
}
