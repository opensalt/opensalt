<?php

namespace App\Form\Validator;

use Jose\Component\KeyManagement\JWKFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidPublicKeyValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPublicKey) {
            throw new UnexpectedTypeException($constraint, ValidPublicKey::class);
        }

        if (null === $value || '' === $value) {
            // Let NotBlank / NotNull handle those errors
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        try {
            try {
                JWKFactory::createFromJsonObject($value);
            } catch (\Exception $e) {
                JWKFactory::createFromKey($value);
            }

            return;
        } catch (\Exception $e) {
            $this->context->buildViolation($e->getMessage())
                ->addViolation();
        }

        $this->context->buildViolation($constraint->message)
            ->addViolation();
    }
}
