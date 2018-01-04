<?php

namespace App\Command;

use Symfony\Component\Validator\ConstraintViolationListInterface;

interface CommandInterface
{
    public function hasValidationErrors(): bool;
    public function getValidationErrors(): ?ConstraintViolationListInterface;
    public function setValidationErrors(ConstraintViolationListInterface $violationList): void;
}
