<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteAssociationGroupHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteAssociationGroupCommand $command */
        $command = $event->getCommand();

        $associationGroup = $command->getAssociationGroup();
        $this->validate($command, $associationGroup);

        $this->framework->deleteAssociationGroup($associationGroup);

        /** @psalm-suppress InvalidArrayOffset */
        $notification = new NotificationEvent(
            'G02',
            sprintf('Association Group "%s" deleted', $associationGroup->getTitle()),
            $associationGroup->getLsDoc(),
            [
                'assocGrp-d' => [
                    $associationGroup->getId() => $associationGroup->getIdentifier(),
                ]
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
