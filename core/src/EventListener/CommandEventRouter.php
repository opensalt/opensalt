<?php

namespace App\EventListener;

use App\Command\CommandInterface;
use App\Entity\ChangeEntry;
use App\Entity\NotificationOnlyChangeEntry;
use App\Entity\User\User;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Service\LoggerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CommandEventRouter implements EventSubscriberInterface
{
    use LoggerTrait;

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [CommandEvent::class => 'routeCommand'];
    }

    public function routeCommand(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
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

        $this->info('Routing command', ['command' => $command::class]);

        try {
            $dispatcher->dispatch($event, $command::class);

            if ($validationErrors = $command->getValidationErrors()) {
                $errors = [];
                foreach ($validationErrors as $error) {
                    $errors[] = $error->getMessage();
                }
                $errorString = implode(' ', $errors);
                $this->info('Error in command', ['command' => $command::class, 'errors' => $errorString]);
            }
        } catch (\Exception $e) {
            $this->info('Exception in command', ['command' => $command::class, 'exception' => $e]);

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
                $changeEntry = new ChangeEntry(null, $this->getCurrentUser(), $command::class, []);
            }
        }

        if ($changeEntry instanceof NotificationOnlyChangeEntry) {
            // Do not store the entry, we only want to send a notification
            return $changeEntry;
        }

        $this->em->persist($changeEntry);

        return $changeEntry;
    }

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

        return $user->getUserIdentifier();
    }

    protected function resolveNotification(CommandInterface $command): NotificationEvent
    {
        $notification = $command->getNotificationEvent();
        if (null === $notification) {
            $notification = new NotificationEvent('X01', 'Command '.$command::class.' handled', null, [], false);
        }

        if (null === $notification->getUsername()) {
            $notification->setUsername($this->getCurrentUsername());
        }

        $notification->resolveChanged();

        return $notification;
    }
}
