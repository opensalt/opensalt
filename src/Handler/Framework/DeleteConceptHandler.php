<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteConceptCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteConceptHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteConceptCommand $command */
        $command = $event->getCommand();

        $concept = $command->getConcept();
        $this->validate($command, $concept);

        $this->framework->deleteConcept($concept);
    }
}
