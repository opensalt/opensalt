<?php

namespace App\Form\Validator;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidMfaCodeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidMfaCode) {
            throw new UnexpectedTypeException($constraint, ValidMfaCode::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof TwoFactorInterface) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        if (!$this->totpAuthenticator->checkCode($user, $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }
    }
}
