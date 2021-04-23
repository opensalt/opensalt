<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateAssociationCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateAssociationHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateAssociationCommand $command */
        $command = $event->getCommand();

        $association = $command->getAssociation();
        $this->validate($command, $association);

        /** @psalm-suppress InvalidArrayOffset */
        $notification = new NotificationEvent(
            'A07',
            sprintf('Association "%s" modified', $association->getIdentifier()),
            $association->getLsDoc(),
            [
                'assoc-u' => [
                    $association->getId() => $association->getIdentifier(),
                ]
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
