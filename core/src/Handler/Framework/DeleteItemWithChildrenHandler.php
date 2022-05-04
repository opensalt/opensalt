<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteItemWithChildrenCommand;
use App\Entity\Framework\LsItem;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteItemWithChildrenHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteItemWithChildrenCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        //$hasChildren = $item->getChildren();

        $this->validate($command, $item);

        $doc = $item->getLsDoc();
        $itemList = [];
        $this->getChildList($item, $itemList);

        $this->framework->deleteItemWithChildren($item);

        $notification = new NotificationEvent(
            'I05',
            sprintf('"%s" and children deleted', $item->getShortStatement()),
            $doc,
            [
                'item-d' => $itemList,
            ]
        );
        $command->setNotificationEvent($notification);
    }

    protected function getChildList(LsItem $item, array &$list): void
    {
        $list[$item->getId()] = $item->getIdentifier();

        $children = $item->getChildren();
        foreach ($children as $child) {
            $this->getChildList($child, $list);
        }
    }
}
