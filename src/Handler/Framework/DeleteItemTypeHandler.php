<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteItemTypeCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteItemTypeHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteItemTypeCommand $command */
        $command = $event->getCommand();

        $itemType = $command->getItemType();
        $this->validate($command, $itemType);

        $this->framework->deleteItemType($itemType);
    }
}
