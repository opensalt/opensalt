<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddExemplarToItemCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddExemplarToItemHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddExemplarToItemCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        $url = $command->getUrl();
        $file = $command->getFile();

        $this->validate($command, $item);
        $this->validate($command, $command);

        $association = $this->framework->addExemplarToItem($item, $url, $file);
        $command->setAssociation($association);

        $notification = new NotificationEvent(
            'A02',
            sprintf('Exemplar (%s) added to "%s"', $url, $item->getShortStatement()),
            $item->getLsDoc(),
            [
                'item-u' => [
                    $item->getId() => $item->getIdentifier(),
                ],
                'assoc-a' => [
                    $association,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
