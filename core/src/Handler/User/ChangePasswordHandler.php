<?php

namespace App\Handler\User;

use App\Command\User\ChangePasswordCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChangePasswordHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ChangePasswordCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();
        $encryptedPassword = $command->getEncryptedPassword();

        $user->setPassword($encryptedPassword);

        $this->em->persist($user);

        $command->setNotificationEvent(new NotificationEvent(
            'U05',
            sprintf('User "%s" changed password', $user->getUserIdentifier()),
            null
        ));
    }
}
