<?php

namespace App\Handler\User;

use App\Command\User\AddUserCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddUserHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();
        $this->validate($command, $user);

        $encryptedPassword = $command->getEncryptedPassword();
        $user->setPassword($encryptedPassword);

        $this->em->persist($user);

        $command->setNotificationEvent(new NotificationEvent(
            'U01',
            sprintf('User "%s" added to "%s"', $user->getUserIdentifier(), $user->getOrg()->getName()),
            null
        ));
    }
}
