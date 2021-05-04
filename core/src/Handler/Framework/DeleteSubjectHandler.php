<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteSubjectCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteSubjectHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteSubjectCommand $command */
        $command = $event->getCommand();

        $subject = $command->getSubject();
        $this->validate($command, $subject);

        $this->framework->deleteSubject($subject);
    }
}
