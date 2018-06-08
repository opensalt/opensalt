<?php

namespace App\EventListener;

use App\Event\NotificationEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationLoggingListener implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [NotificationEvent::class => 'logNotification'];
    }

    public function logNotification(NotificationEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->logger->info('Notification Received', ['msgId' => $event->getMessageId(), 'msg' => $event->getMessage(), 'user' => $event->getUsername()]);
    }
}
