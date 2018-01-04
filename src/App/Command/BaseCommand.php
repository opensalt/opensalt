<?php

namespace App\Command;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class BaseCommand implements CommandInterface
{
    protected $validationErrors;

    public function hasValidationErrors(): bool
    {
        return \count($this->validationErrors ?? []) !== 0;
    }

    public function getValidationErrors(): ?ConstraintViolationListInterface
    {
        return $this->validationErrors;
    }

    public function setValidationErrors(ConstraintViolationListInterface $violationList): void
    {
        $this->validationErrors = $violationList;
    }
}
