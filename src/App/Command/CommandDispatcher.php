<?php

namespace App\Command;

use App\Event\CommandEvent;

trait CommandDispatcher
{
    /**
     * Send a command to be handled
     *
     * @param CommandInterface $command
     */
    protected function sendCommand(CommandInterface $command): void
    {
        $dispatcher = $this->dispatcher ?? null;

        if (null === $dispatcher) {
           $dispatcher = $this->get('event_dispatcher');
        }

        $dispatcher->dispatch(
            CommandEvent::class,
            new CommandEvent($command)
        );
    }
}
