<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PasswordField extends Constraint
{
    public $message = 'Password does not match required criteria';
}
