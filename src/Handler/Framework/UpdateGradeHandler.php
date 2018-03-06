<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateGradeCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateGradeHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateGradeCommand $command */
        $command = $event->getCommand();

        $grade = $command->getGrade();
        $this->validate($command, $grade);
    }
}
