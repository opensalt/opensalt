<?php

namespace App\EventListener;

use App\Command\CommandInterface;
use App\Entity\ChangeEntry;
use App\Entity\NotificationOnlyChangeEntry;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\User\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

class CommandEventRouter implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
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

    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [CommandEvent::class => 'routeCommand'];
    }

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
            $dispatcher->dispatch($event, \get_class($command));

            if ($validationErrors = $command->getValidationErrors()) {
                $errors = [];
                /** @var ConstraintViolationInterface $error */
                foreach ($validationErrors as $error) {
                    $errors[] = $error->getMessage();
                }
                $errorString = implode(' ', $errors);
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
        $dispatcher->dispatch($notification, NotificationEvent::class);
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
