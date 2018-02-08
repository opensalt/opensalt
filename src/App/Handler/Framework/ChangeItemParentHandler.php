<?php

namespace App\Handler\Framework;

use App\Command\Framework\ChangeItemParentCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use CftfBundle\Entity\LsAssociation;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ChangeItemParentHandler
 *
 * @DI\Service()
 */
class ChangeItemParentHandler extends BaseDoctrineHandler
{
    /**
     * @DI\Observe(App\Command\Framework\ChangeItemParentCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
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
            $parentTitle = substr($dto->lsItem->getLsDoc(), 0, 60);
        } else {
            $parentTitle = $parent->getShortStatement();
            $changedItems[$parent->getId()] = $parent->getIdentifier();
        }
        $dto->lsItem->setUpdatedAt(new \DateTime());
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
