<?php

namespace App\Handler\User;

use App\Command\User\DeleteUserCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteUserHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();

        $this->em->remove($user);

        $command->setNotificationEvent(new NotificationEvent(
            'U03',
            sprintf('User "%s" deleted', $user->getUserIdentifier()),
            null
        ));
    }
}
