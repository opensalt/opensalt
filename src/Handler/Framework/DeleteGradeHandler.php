<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteGradeCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteGradeHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteGradeCommand $command */
        $command = $event->getCommand();

        $grade = $command->getGrade();
        $this->validate($command, $grade);

        $this->framework->deleteGrade($grade);
    }
}
