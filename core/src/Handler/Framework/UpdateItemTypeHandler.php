<?php

declare(strict_types=1);

namespace App\Handler\Framework;

use App\Command\Framework\UpdateItemTypeCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateItemTypeHandler extends BaseFrameworkHandler
{
    #[\Override]
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateItemTypeCommand $command */
        $command = $event->getCommand();

        $itemType = $command->getItemType();
        $this->validate($command, $itemType);
    }
}
