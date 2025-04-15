<?php

namespace App\Command;

use App\Entity\ChangeEntry;
use App\Event\NotificationEvent;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class BaseCommand implements CommandInterface
{
    protected ?ConstraintViolationListInterface $validationErrors = null;
    protected ?NotificationEvent $notificationEvent = null;
    protected ?ChangeEntry $changeEntry = null;

    #[\Override]
    public function hasValidationErrors(): bool
    {
        return 0 !== \count($this->validationErrors ?? []);
    }

    #[\Override]
    public function getValidationErrors(): ?ConstraintViolationListInterface
    {
        return $this->validationErrors;
    }

    #[\Override]
    public function setValidationErrors(ConstraintViolationListInterface $violationList): void
    {
        $this->validationErrors = $violationList;
    }

    #[\Override]
    public function setNotificationEvent(NotificationEvent $event): void
    {
        $this->notificationEvent = $event;
    }

    #[\Override]
    public function getNotificationEvent(): ?NotificationEvent
    {
        return $this->notificationEvent;
    }

    #[\Override]
    public function setChangeEntry(ChangeEntry $entry): void
    {
        $this->changeEntry = $entry;
    }

    #[\Override]
    public function getChangeEntry(): ?ChangeEntry
    {
        return $this->changeEntry;
    }
}
