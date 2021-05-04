<?php

namespace App\Handler\User;

use App\Command\User\UpdateOrganizationCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateOrganizationHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateOrganizationCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $organization = $command->getOrg();

        $this->em->persist($organization);

        $command->setNotificationEvent(new NotificationEvent(
            'O04',
            sprintf('Organization "%s" modified', $organization->getName()),
            null
        ));
    }
}
