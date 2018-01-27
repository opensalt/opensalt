<?php

namespace App\EventListener;

use App\Command\CommandInterface;
use App\Entity\ChangeEntry;
use App\Entity\NotificationOnlyChangeEntry;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Psr\Log\LoggerInterface;
use Salt\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class CommandEventRouter
 *
 * @DI\Service()
 */
class CommandEventRouter
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * AddDocumentHandler constructor.
     *
     * @DI\InjectParams({
     *     "registry" = @DI\Inject("doctrine"),
     *     "tokenStorage" = @DI\Inject("security.token_storage"),
     *     "logger" = @DI\Inject("logger"),
     * })
     */
    public function __construct(ManagerRegistry $registry, TokenStorageInterface $tokenStorage, LoggerInterface $logger)
    {
        $this->em = $registry->getManager();
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @DI\Observe(App\Event\CommandEvent::class)
     *
     * @throws \Exception
     */
    public function routeCommand(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var CommandInterface $command */
        $command = $event->getCommand();

        $this->em->getConnection()->beginTransaction();

        try {
            $this->sendCommand($event, $dispatcher);

            $notification = $command->getNotificationEvent();
            $changeEntry = $this->addChangeEntry($command, $notification);

            $this->em->flush();

            // We need to resolve after the flush in order to have created ids
            $notification = $this->resolveNotification($command);
            $this->updateChangeEntry($command, $changeEntry, $notification);

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();

            throw $e;
        }

        $this->sendNotification($dispatcher, $notification);
    }

    /**
     * @throws \Exception
     */
    protected function sendCommand(CommandEvent $event, EventDispatcherInterface $dispatcher): void
    {
        $command = $event->getCommand();

        $this->logger->info('Routing command', ['command' => \get_class($command)]);

        try {
            $dispatcher->dispatch(\get_class($command), $event);

            if ($command->getValidationErrors()) {
                $errorString = (string) $command->getValidationErrors();
                $this->logger->info('Error in command', ['command' => \get_class($command), 'errors' => $errorString]);
            }
        } catch (\Exception $e) {
            $this->logger->info('Exception in command', ['command' => \get_class($command), 'exception' => $e]);

            throw $e;
        }
    }

    protected function addChangeEntry(CommandInterface $command, ?NotificationEvent $notification): ChangeEntry
    {
        $changeEntry = $command->getChangeEntry();
        if (null === $changeEntry) {
            if (null !== $notification) {
                $changeEntry = new ChangeEntry($notification->getDoc(), $this->getCurrentUser(), $notification->getMessage(), $notification->getChanged());
            } else {
                $changeEntry = new ChangeEntry(null, $this->getCurrentUser(), \get_class($command), []);
            }
        }

        if ($changeEntry instanceof NotificationOnlyChangeEntry) {
            // Do not store the entry, we only want to send a notification
            return $changeEntry;
        }

        // We only store the last change in the table, older entries are in the audit table
        $change = $this->em->getRepository(ChangeEntry::class)->findOneBy(['doc' => $changeEntry->getDoc()]);
        if (null === $change) {
            $change = $changeEntry;
            $this->em->persist($change);
        } else {
            $change->updateTo($changeEntry);
        }

        return $change;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateChangeEntry(CommandInterface $command, ChangeEntry $change, NotificationEvent $notification): void
    {
        if (null !== $command->getChangeEntry()) {
            // Do not do an update if the change entry was explicit
            return;
        }

        if ($change->getChanged() === $notification->getChanged()) {
            // Not changed
            return;
        }

        $this->em->getRepository(ChangeEntry::class)->updateChanged($change, $notification);
    }

    protected function sendNotification(EventDispatcherInterface $dispatcher, NotificationEvent $notification): void
    {
        $dispatcher->dispatch(NotificationEvent::class, $notification);
    }

    protected function getCurrentUser(): ?User
    {
        $user = null;

        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
            if (!$user instanceof User) {
                $user = null;
            }
        }

        return $user;
    }

    protected function getCurrentUsername(): string
    {
        $user = $this->getCurrentUser();
        if (null === $user) {
            return 'Unknown User';
        }

        return $user->getUsername();
    }

    protected function resolveNotification(CommandInterface $command): NotificationEvent
    {
        $notification = $command->getNotificationEvent();
        if (null === $notification) {
            $notification = new NotificationEvent('X01', 'Command '.\get_class($command).' handled', null, [], false);
        }

        if (null === $notification->getUsername()) {
            $notification->setUsername($this->getCurrentUsername());
        }

        $notification->resolveChanged();

        return $notification;
    }
}
