<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteItemCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteItemHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteItemCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        $hasChildren = $item->getChildren();

        if ($hasChildren->isEmpty()) {
            throw new \Exception('Cannot delete an item with children.');
        }

        $this->validate($command, $item);

        $this->framework->deleteItem($item);

        $notification = new NotificationEvent(
            'I04',
            sprintf('"%s" and direct associations deleted', $item->getShortStatement()),
            $item->getLsDoc(),
            [
                'item-d' => [
                    $item->getId() => $item->getIdentifier(),
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
