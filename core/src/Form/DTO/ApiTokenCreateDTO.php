<?php

declare(strict_types=1);

namespace App\Form\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ApiTokenCreateDTO
{
    #[Assert\NotBlank(message: 'Please provide a name for the token.')]
    #[Assert\Length(max: 255, maxMessage: 'The name cannot be longer than {{ limit }} characters.')]
    public ?string $name = null;

    /**
     * Optional expiry date (date-only). Must be today or within one year from today.
     */
    public ?\DateTimeInterface $expiresAt = null;

    /**
     * Validate expiresAt is within [today, today + 1 year].
     */
    #[Assert\Callback]
    public function validateExpiry(ExecutionContextInterface $context): void
    {
        if (null === $this->expiresAt) {
            return;
        }

        $today = new \DateTimeImmutable('today');
        $max = $today->modify('+1 year');

        // Normalize to date-only by comparing dates at midnight
        $expDate = \DateTimeImmutable::createFromFormat('Y-m-d', $this->expiresAt->format('Y-m-d'));

        if ($expDate < $today) {
            $context->buildViolation('The expiry date must be today or in the future.')
                ->atPath('expiresAt')
                ->addViolation();

            return;
        }

        if ($expDate > $max) {
            $context->buildViolation('The expiry date must be within one year from today.')
                ->atPath('expiresAt')
                ->addViolation();
        }
    }
}
