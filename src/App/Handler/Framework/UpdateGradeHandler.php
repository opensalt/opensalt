<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateGradeCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateGradeHandler
 *
 * @DI\Service()
 */
class UpdateGradeHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\UpdateGradeCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateGradeCommand $command */
        $command = $event->getCommand();

        $grade = $command->getGrade();
        $this->validate($command, $grade);

//        $dispatcher->dispatch(UpdateGradeEvent::class, new UpdateGradeEvent());
    }
}
