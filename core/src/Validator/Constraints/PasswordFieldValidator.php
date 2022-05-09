<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordFieldValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PasswordField) {
            return;
        }

        $asserts = 0;

        if (preg_match('/[A-Z]/', $value, $matches)) {
            ++$asserts;
        }

        if (preg_match('/[a-z]/', $value, $matches)) {
            ++$asserts;
        }

        if (preg_match('/\d/', $value, $matches)) {
            ++$asserts;
        }

        if (preg_match('/[_\W]/', $value, $matches)) {
            ++$asserts;
        }

        if ($asserts < 3) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
