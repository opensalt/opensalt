<?php

namespace App\EventListener;

use App\Event\NotificationEvent;
use App\Service\LoggerTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationLoggingListener implements EventSubscriberInterface
{
    use LoggerTrait;

    public static function getSubscribedEvents(): array
    {
        return [NotificationEvent::class => 'logNotification'];
    }

    public function logNotification(NotificationEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->info('Notification Received', ['msgId' => $event->getMessageId(), 'msg' => $event->getMessage(), 'user' => $event->getUsername()]);
    }
}
