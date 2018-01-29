<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddGradeCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddGradeHandler
 *
 * @DI\Service()
 */
class AddGradeHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddGradeCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddGradeCommand $command */
        $command = $event->getCommand();

        $grade = $command->getGrade();
        $this->validate($command, $grade);

        $this->framework->persistGrade($grade);
    }
}
