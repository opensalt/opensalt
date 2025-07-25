<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\DeleteAccessGroupCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteAccessGroupHandler extends BaseUserHandler
{
    #[\Override]
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteAccessGroupCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $accessGroup = $command->getAccessGroup();

        $this->em->remove($accessGroup);

        $command->setNotificationEvent(new NotificationEvent(
            'O03',
            sprintf('Organization "%s" deleted', $accessGroup->getName()),
            null
        ));
    }
}
