<?php

namespace App\Handler\User;

use App\Command\User\SuspendUserCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SuspendUserHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var SuspendUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();
        $user->suspendUser();

        $command->setNotificationEvent(new NotificationEvent(
            'U06',
            sprintf('User "%s" suspended', $user->getUsername()),
            null
        ));
    }
}
