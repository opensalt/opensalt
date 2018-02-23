<?php

namespace Salt\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordFieldValidator extends ConstraintValidator
{
    private $asserts = 0;

    public function validate($value, Constraint $constraint)
    {
        if (preg_match('/[A-Z]/', $value, $matches)) {
            ++$this->asserts;
        }

        if (preg_match('/[a-z]/', $value, $matches)) {
            ++$this->asserts;
        }

        if (preg_match('/[0-9]/', $value, $matches)) {
            ++$this->asserts;
        }

        if (preg_match('/[_\W]/', $value, $matches)) {
            ++$this->asserts;
        }

        if ($this->asserts < 3) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
