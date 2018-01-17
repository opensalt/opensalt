<?php

namespace App\EventListener;

use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Kreait\Firebase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
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
 *                 "at": <milliseconds since epoch>,
 *                 "msgId": "message id here",
 *                 "msg": "message here",
 *                 "by": "username here",
 *                 "changes": [
 *                     "{item,doc,assoc}-{a,u,d,l,ul}": [
 *                         object-id: "object identifier", ...
 *                     ],
 *                 ],
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
     * @var string
     */
    private $prefix;

    /**
     * @DI\InjectParams({
     *     "firebase" = @DI\Inject(Firebase::class),
     *     "logger" = @DI\Inject("logger"),
     *     "prefix" = @DI\Inject("%firebase_prefix%"),
     * })
     */
    public function __construct(?Firebase $firebase, LoggerInterface $logger, ?string $prefix = null)
    {
        $this->firebase = $firebase;
        $this->logger = $logger;
        $this->prefix = !empty($prefix) ? $prefix : 'opensalt';
    }

    /**
     * @DI\Observe(App\Event\NotificationEvent::class)
     */
    public function handleNotification(NotificationEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        if (null === $this->firebase) {
            $this->logger->debug('Firebase not enabled');
            return;
        }

        $doc = $event->getDoc();
        if (null === $doc) {
            // Only add doc change records to firebase
            return;
        }
        $docId = $doc->getId();

        $notification = [
            'msgId' => $event->getMessageId(),
            'msg' => $event->getMessage(),
            'by' => $event->getUsername(),
            'changes' => $event->getChanged(),
            'at' => (int) (new \DateTime())->format('Uv'),
        ];

        // Send the notification after the response is sent
        $dispatcher->addListener(
            'kernel.terminate',
            function (Event $event) use ($notification, $docId) {
                $this->addDocChangeToFirebase($notification, $docId);
            },
            10
        );

        try {
            // Clean up old notifications around 1% of the time
            if (1 === random_int(1, 100)) {
                $dispatcher->addListener(
                    'kernel.terminate',
                    function (Event $event) {
                        $this->cleanupOldNotifications();
                    },
                    -10
                );
            }
        } catch (\Exception $e) {
            // Ignore if cannot get enough entropy for random_int
        }
    }

    protected function addDocChangeToFirebase(array $notification, int $docId): void
    {
        $this->logger->info('Adding to firebase', [
            'msg' => $notification['msg'],
            'user' => $notification['by'],
            'changes' => $notification['changes'],
        ]);

        $path = '/'.$this->prefix."/doc/{$docId}/notification";
        $db = $this->firebase->getDatabase();
        $db->getReference($path)->push($notification);
    }

    protected function cleanupOldNotifications(): void
    {
        $expireBefore = (new \DateTime('now - 5 minutes'))->format('Uv');
        $removeKeys = [];
        $path = '/'.$this->prefix.'/doc';

        $db = $this->firebase->getDatabase();
        $docs = $db->getReference($path)->getValue();
        if (null !== $docs) {
            foreach ($docs as $doc => $content) {
                if (array_key_exists('notification', $content)) {
                    foreach ($content['notification'] as $key => $notification) {
                        if ($expireBefore > $notification['at']) {
                            $removeKeys["/doc/{$doc}/notification/{$key}"] = null;
                        }
                    }
                }
            }

            if (0 < \count($removeKeys)) {
                $db->getReference()->update($removeKeys);
            }
        }
    }
}
