<?php

namespace App\Handler\User;

use App\Command\User\AddOrganizationCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddOrganizationHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddOrganizationCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $organization = $command->getOrg();
        $this->validate($command, $organization);

        $this->em->persist($organization);

        $command->setNotificationEvent(new NotificationEvent(
            'O01',
            sprintf('Organization "%s" added', $organization->getName()),
            null
        ));
    }
}
