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
        $this->get('event_dispatcher')->dispatch(
            CommandEvent::class,
            new CommandEvent($command)
        );
    }
}
