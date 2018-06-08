<?php

namespace App\Handler\User;

use App\Command\User\ActivateUserCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ActivateUserHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ActivateUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();
        $user->activateUser();

        $command->setNotificationEvent(new NotificationEvent(
            'U07',
            sprintf('User "%s" unsuspended', $user->getUsername()),
            null
        ));
    }
}
