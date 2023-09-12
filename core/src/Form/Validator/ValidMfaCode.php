<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidMfaCode extends Constraint
{
    public string $message = 'The entered code is not valid';
}
