<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddAssociationGroupCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddAssociationGroupHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddAssociationGroupCommand $command */
        $command = $event->getCommand();

        $associationGroup = $command->getAssociationGroup();
        $this->validate($command, $associationGroup);

        $this->framework->persistAssociationGroup($associationGroup);

        $notification = new NotificationEvent(
            'G01',
            sprintf('Association Group "%s" added', $associationGroup->getTitle()),
            $associationGroup->getLsDoc(),
            [
                'assocGrp-a' => [
                    $associationGroup,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
