<?php

namespace App\EventListener;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Kreait\Firebase;
use Kreait\Firebase\Database;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service()
 *
 * Firebase data structure
 *
 * "doc": {
 *     doc_id: {
 *         "notification": [
 *             KEY: {
 *                 "message": "message here",
 *                 "username": "username here",
 *                 "itemIds": [],
 *                 "associationIds": [],
 *             }
 *         ]
 *     }
 * }
 */
class NotificationToFirebaseListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Firebase
     */
    private $firebase;

    /**
     * @DI\InjectParams({
     *     "firebase" = @DI\Inject(Firebase::class),
     *     "logger" = @DI\Inject("logger")
     * })
     */
    public function __construct(?Firebase $firebase, LoggerInterface $logger)
    {
        $this->firebase = $firebase;
        $this->logger = $logger;
    }

    /**
     * @DI\Observe(App\Event\NotificationEvent::class)
     */
    public function handleNotification(NotificationEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        if (null === $this->firebase) {
            $this->logger->info('Firebase not enabled');
            return;
        }

        $doc = $event->getDoc();
        if (null === $doc) {
            // Only add doc change records to firebase
            return;
        }
        $docId = $doc->getId();

        $notification = [
            'msg' => $event->getMessage(),
            'by' => $event->getUsername(),
            'changes' => $event->getChanged(),
            'at' => Database::SERVER_TIMESTAMP,
        ];

        $this->addDocChangeToFirebase($notification, $docId);
    }

    protected function addDocChangeToFirebase(array $notification, int $docId): void
    {
        $this->logger->info('Adding to firebase', [
            'msg' => $notification['msg'],
            'user' => $notification['by'],
            'changes' => $notification['changes'],
        ]);

        $path = "/doc/{$docId}/notification";
        $db = $this->firebase->getDatabase();
        $db->getReference($path)->push($notification);
    }
}
