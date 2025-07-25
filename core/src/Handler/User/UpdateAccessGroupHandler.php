<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\UpdateAccessGroupCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateAccessGroupHandler extends BaseUserHandler
{
    #[\Override]
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateAccessGroupCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $accessGroup = $command->getAccessGroup();

        $this->em->persist($accessGroup);

        $command->setNotificationEvent(new NotificationEvent(
            'O04',
            sprintf('Organization "%s" modified', $accessGroup->getName()),
            null
        ));
    }
}
