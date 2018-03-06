<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddSubjectCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddSubjectHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddSubjectCommand $command */
        $command = $event->getCommand();

        $subject = $command->getSubject();
        $this->validate($command, $subject);

        $this->framework->persistSubject($subject);
    }
}
