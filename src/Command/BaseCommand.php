<?php

namespace App\Command;

use App\Entity\ChangeEntry;
use App\Event\NotificationEvent;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class BaseCommand implements CommandInterface
{
    /**
     * @var ConstraintViolationListInterface|null
     */
    protected $validationErrors;

    /**
     * @var NotificationEvent|null
     */
    protected $notificationEvent;

    /**
     * @return ChangeEntry
     */
    protected $changeEntry;

    public function hasValidationErrors(): bool
    {
        return 0 !== \count($this->validationErrors ?? []);
    }

    public function getValidationErrors(): ?ConstraintViolationListInterface
    {
        return $this->validationErrors;
    }

    public function setValidationErrors(ConstraintViolationListInterface $violationList): void
    {
        $this->validationErrors = $violationList;
    }

    public function setNotificationEvent(NotificationEvent $event): void
    {
        $this->notificationEvent = $event;
    }

    public function getNotificationEvent(): ?NotificationEvent
    {
        return $this->notificationEvent;
    }

    public function setChangeEntry(ChangeEntry $entry): void
    {
        $this->changeEntry = $entry;
    }

    public function getChangeEntry(): ?ChangeEntry
    {
        return $this->changeEntry;
    }
}
