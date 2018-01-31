<?php

namespace App\Command;

use App\Entity\ChangeEntry;
use App\Event\NotificationEvent;
use Symfony\Component\Validator\ConstraintViolationListInterface;

interface CommandInterface
{
    public function hasValidationErrors(): bool;
    public function getValidationErrors(): ?ConstraintViolationListInterface;
    public function setValidationErrors(ConstraintViolationListInterface $violationList): void;

    public function setNotificationEvent(NotificationEvent $event): void;
    public function getNotificationEvent(): ?NotificationEvent;

    public function setChangeEntry(ChangeEntry $entry): void;
    public function getChangeEntry(): ?ChangeEntry;
}
