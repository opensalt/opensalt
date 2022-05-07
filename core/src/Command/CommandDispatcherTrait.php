<?php

namespace App\Command;

use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait CommandDispatcherTrait
{
    protected ?EventDispatcherInterface $dispatcher = null;

    #[Required]
    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Send a command to be handled.
     */
    protected function sendCommand(CommandInterface $command): void
    {
        $dispatcher = $this->dispatcher ?? null;

        if (null === $dispatcher) {
            throw new \RuntimeException('Cannot access dispatcher');
        }

        $dispatcher->dispatch(new CommandEvent($command));
    }
}
