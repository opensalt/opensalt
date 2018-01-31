<?php

namespace App\Command;

use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

trait CommandDispatcherTrait
{
    /**
     * Send a command to be handled
     *
     * @param CommandInterface $command
     */
    protected function sendCommand(CommandInterface $command): void
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->dispatcher ?? null;

        if (null === $dispatcher && method_exists($this, 'get')) {
            $dispatcher = $this->get('event_dispatcher');
        }

        if (null === $dispatcher) {
            throw new \RuntimeException('Cannot access dispatcher');
        }

        $dispatcher->dispatch(
            CommandEvent::class,
            new CommandEvent($command)
        );
    }
}
