<?php

namespace App\EventListener;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service()
 */
class NotificationListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AddDocumentHandler constructor.
     *
     * @DI\InjectParams({
     *     "logger" = @DI\Inject("logger"),
     * })
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @DI\Observe(App\Event\NotificationEvent::class)
     */
    public function handleNotification(NotificationEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->logger->info('Notification Received', ['msg' => $event->getMessage()]);
    }
}
