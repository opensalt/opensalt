<?php

namespace App\Form\DTO;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as CustomAssert;

class ChangePasswordDTO
{
    /**
     * @var string
     *
     * @SecurityAssert\UserPassword(message="The current password is incorrect", groups={"registration"})
     */
    public $oldPassword;

    /**
     * @var string
     *
     * @Assert\Length(
     *      min=8,
     *      max=4096,
     *      minMessage="Password must be at least {{ limit }} characters long",
     *      maxMessage="Password cannot be longer than {{ limit }} characters",
     *      groups={"registration"} 
     * )
     * @CustomAssert\PasswordField(groups={"registration"})
     * 
     */
    public $newPassword;
}
