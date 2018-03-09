<?php

namespace App\Handler\User;

use App\Command\User\DeleteOrganizationCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteOrganizationHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteOrganizationCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $organization = $command->getOrg();

        $this->em->remove($organization);

        $command->setNotificationEvent(new NotificationEvent(
            'O03',
            sprintf('Organization "%s" deleted', $organization->getName()),
            null
        ));
    }
}
