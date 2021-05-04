<?php

namespace App\Handler\Framework;

use App\Command\Framework\RemoveChildCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RemoveChildHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var RemoveChildCommand $command */
        $command = $event->getCommand();

        $parent = $command->getParent();
        $child = $command->getChild();

        $lsItemRepo = $this->em->getRepository(LsItem::class);
        $associations = $lsItemRepo->findChildAssociations($parent, $child);
        $removedList = [];
        $lastAssociation = null;
        foreach ($associations as $association) {
            $removedList[$association->getId()] = $association->getIdentifier();
            $lastAssociation = $association;
        }

        if (null !== $lastAssociation) {
            $fromTitle = $this->getTitle($lastAssociation->getOrigin());
            $toTitle = $this->getTitle($lastAssociation->getDestination());
            $notification = new NotificationEvent(
                'A06',
                sprintf('"%s" association deleted from "%s" to "%s"', LsAssociation::CHILD_OF, $fromTitle, $toTitle),
                $parent->getLsDoc(),
                [
                    'assoc-d' => $removedList,
                ]
            );
            $command->setNotificationEvent($notification);
        }

        $lsItemRepo->removeChild($parent, $child);
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
