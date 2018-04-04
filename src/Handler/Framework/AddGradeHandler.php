<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddGradeCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddGradeHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddGradeCommand $command */
        $command = $event->getCommand();

        $grade = $command->getGrade();
        $this->validate($command, $grade);

        $this->framework->persistGrade($grade);
    }
}
