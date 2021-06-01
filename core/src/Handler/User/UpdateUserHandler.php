<?php

namespace App\Handler\User;

use App\Command\User\UpdateUserCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateUserHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();

        $this->em->persist($user);

        $command->setNotificationEvent(new NotificationEvent(
            'U04',
            sprintf('User "%s" modified', $user->getUserIdentifier()),
            null
        ));
    }
}
