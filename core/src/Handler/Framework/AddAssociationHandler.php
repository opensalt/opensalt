<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddAssociationCommand;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddAssociationHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddAssociationCommand $command */
        $command = $event->getCommand();

        $association = $command->getAssociation();
        $this->validate($command, $association);

        $this->framework->persistAssociation($association);

        $fromTitle = $this->getTitle($association->getOrigin());
        $toTitle = $this->getTitle($association->getDestination());
        $notification = new NotificationEvent(
            'A01',
            sprintf('"%s" association added from "%s" to "%s"', $association->getType(), $fromTitle, $toTitle),
            $association->getLsDoc(),
            [
                'assoc-a' => [
                    $association,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }

    protected function getTitle($obj): string
    {
        if (null === $obj) {
            return 'NONE';
        }

        if (is_string($obj)) {
            return $obj;
        }

        if ($obj instanceof LsItem || $obj instanceof LsDoc) {
            return $obj->getShortStatement();
        }

        return 'UNKNOWN';
    }
}
