<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\AddAccessGroupCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddAccessGroupHandler extends BaseUserHandler
{
    #[\Override]
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddAccessGroupCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $accessGroup = $command->getAccessGroup();
        $this->validate($command, $accessGroup);

        $this->em->persist($accessGroup);

        $command->setNotificationEvent(new NotificationEvent(
            'O01',
            sprintf('Organization "%s" added', $accessGroup->getName()),
            null
        ));
    }
}
