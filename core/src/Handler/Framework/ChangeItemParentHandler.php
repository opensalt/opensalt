<?php

namespace App\Handler\Framework;

use App\Command\Framework\ChangeItemParentCommand;
use App\Entity\Framework\LsAssociation;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChangeItemParentHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ChangeItemParentCommand $command */
        $command = $event->getCommand();

        $dto = $command->getDto();
        $this->validate($command, $dto);

        $changedItems = [];
        $changedItems[$dto->lsItem->getId()] = $dto->lsItem->getIdentifier();

        $parent = $dto->lsItem->getParentItem();
        if (null === $parent) {
            $parentTitle = mb_substr($dto->lsItem->getLsDoc(), 0, 60);
        } else {
            $parentTitle = $parent->getShortStatement();
            $changedItems[$parent->getId()] = $parent->getIdentifier();
        }

        $this->em->getRepository(LsAssociation::class)
            ->removeAllAssociationsOfType($dto->lsItem, LsAssociation::CHILD_OF);
        $dto->lsItem->addParent($dto->parentItem);

        if (null !== $dto->parentItem) {
            $changedItems[$dto->parentItem->getId()] = $dto->parentItem->getIdentifier();
        }

        $notification = new NotificationEvent(
            'I02',
            sprintf('Parent of "%s" changed from "%s" to "%s"', $dto->lsItem->getShortStatement(), $parentTitle, $dto->parentItem->getShortStatement()),
            $dto->lsItem->getLsDoc(),
            [
                'item-u' => $changedItems,
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
