<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PasswordField extends Constraint
{
    public string $message = 'Password does not match required criteria';
}
