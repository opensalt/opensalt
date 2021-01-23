<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateConceptCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateConceptHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateConceptCommand $command */
        $command = $event->getCommand();

        $concept = $command->getConcept();
        $this->validate($command, $concept);
    }
}
