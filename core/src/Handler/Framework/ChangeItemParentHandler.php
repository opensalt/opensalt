<?php

declare(strict_types=1);

namespace App\Handler\Framework;

use App\Command\Framework\ChangeItemParentCommand;
use App\Entity\Framework\LsAssociation;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChangeItemParentHandler extends BaseDoctrineHandler
{
    #[\Override]
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
            $parentTitle = mb_substr($dto->lsItem->getLsDoc()->getTitle(), 0, 60);
        } else {
            $parentTitle = $parent->getShortStatement();
            $changedItems[$parent->getId()] = $parent->getIdentifier();
        }

        $assocRepo = $this->em->getRepository(LsAssociation::class);
        $assocRepo->removeAllAssociationsOfType($dto->lsItem, LsAssociation::CHILD_OF);

        $existingAssocs = $assocRepo->findAllChildAssociationsFor($dto->parentItem->getIdentifier());
        $maxSeq = 0;
        foreach ($existingAssocs as $assoc) {
            $seq = $assoc->getSequenceNumber();
            if (null !== $seq && $seq > $maxSeq) {
                $maxSeq = $seq;
            }
        }
        $seqNum = $maxSeq + 1;
        $dto->lsItem->addParent($dto->parentItem, $seqNum);

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
