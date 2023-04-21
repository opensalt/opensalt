<?php

namespace App\Handler\User;

use App\Command\User\ResetMfaUserCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ResetMfaUserHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ResetMfaUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();
        $user->setIsTotpEnabled(false);
        dump($user);

        $command->setNotificationEvent(new NotificationEvent(
            'U09',
            sprintf('User "%s" MFA Reset', $user->getUserIdentifier()),
            null
        ));
    }
}
