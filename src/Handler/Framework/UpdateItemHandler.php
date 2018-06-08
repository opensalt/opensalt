<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateItemCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateItemHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateItemCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        $this->validate($command, $item);

        $item->setUpdatedAt(new \DateTime()); // Timestampable does not follow up the chain

        $this->framework->unlockObject($item);

        $notification = new NotificationEvent(
            'I08',
            sprintf('"%s" modified', $item->getShortStatement()),
            $item->getLsDoc(),
            [
                'item-u' => [
                    $item->getId() => $item->getIdentifier(),
                ],
                'item-ul' => [
                    $item->getId() => $item->getIdentifier(),
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
