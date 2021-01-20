<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateSubjectCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateSubjectHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateSubjectCommand $command */
        $command = $event->getCommand();

        $subject = $command->getSubject();
        $this->validate($command, $subject);
    }
}
