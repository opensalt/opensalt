<?php

namespace App\EventListener;

use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NotificationLoggingListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @DI\Observe(App\Event\NotificationEvent::class)
     */
    public function logNotification(NotificationEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->logger->info('Notification Received', ['msgId' => $event->getMessageId(), 'msg' => $event->getMessage(), 'user' => $event->getUsername()]);
    }
}
