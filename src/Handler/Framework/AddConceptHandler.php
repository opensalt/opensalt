<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddConceptCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddConceptHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddConceptCommand $command */
        $command = $event->getCommand();

        $concept = $command->getConcept();
        $this->validate($command, $concept);

        $this->framework->persistConcept($concept);
    }
}
