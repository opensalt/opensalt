<?php

namespace App\Command;

use App\Event\NotificationEvent;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class BaseCommand implements CommandInterface
{
    protected $validationErrors;

    /**
     * @var NotificationEvent
     */
    protected $notificationEvent;

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

    public function setNotificationEvent(NotificationEvent $event): void
    {
        $this->notificationEvent = $event;
    }

    public function getNotificationEvent(): ?NotificationEvent
    {
        return $this->notificationEvent;
    }
}
