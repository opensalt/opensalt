<?php

namespace Salt\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordFieldValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $asserts = 0;

        if (preg_match('/[A-Z]/', $value, $matches)) {
            ++$asserts;
        }

        if (preg_match('/[a-z]/', $value, $matches)) {
            ++$asserts;
        }

        if (preg_match('/[0-9]/', $value, $matches)) {
            ++$asserts;
        }

        if (preg_match('/[_\W]/', $value, $matches)) {
            ++$asserts;
        }

        if ($asserts < 3) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
