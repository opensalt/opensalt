<?php

namespace App\Handler\Framework;

use App\Command\Framework\CopyItemToDocCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class CopyItemToDocHandler
 *
 * @DI\Service()
 */
class CopyItemToDocHandler extends BaseDoctrineHandler
{
    /**
     * @DI\Observe(App\Command\Framework\CopyItemToDocCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var CopyItemToDocCommand $command */
        $command = $event->getCommand();

        $dto = $command->getDto();
        $this->validate($command, $dto);

        $newItem = $dto->lsItem->copyToLsDoc($dto->lsDoc);
        $this->em->persist($newItem);
        $command->setNewItem($newItem);

        $notification = new NotificationEvent(
            'I03',
            sprintf('Copied "%s" from "%s"', $newItem->getShortStatement(), substr($dto->lsItem->getLsDoc()->getTitle(), 0, 60)),
            $newItem->getLsDoc(),
            [
                'items' => [
                    $newItem->getId() => $newItem->getIdentifier(),
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
