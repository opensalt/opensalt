<?php

namespace App\Command;

use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

trait CommandDispatcherTrait
{
    /**
     * @var EventDispatcherInterface|null
     */
    protected $dispatcher;

    /**
     * @required
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Send a command to be handled.
     *
     * @param CommandInterface $command
     */
    protected function sendCommand(CommandInterface $command): void
    {
        /** @var EventDispatcherInterface|null $dispatcher */
        $dispatcher = $this->dispatcher ?? null;

        if (null === $dispatcher && method_exists($this, 'get')) {
            $dispatcher = $this->get('event_dispatcher');
        }

        if (null === $dispatcher) {
            throw new \RuntimeException('Cannot access dispatcher');
        }

        $dispatcher->dispatch(
            new CommandEvent($command),
            CommandEvent::class
        );
    }
}
