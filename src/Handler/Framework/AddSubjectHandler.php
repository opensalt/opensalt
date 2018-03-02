<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddSubjectCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddSubjectHandler
 *
 * @DI\Service()
 */
class AddSubjectHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddSubjectCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddSubjectCommand $command */
        $command = $event->getCommand();

        $subject = $command->getSubject();
        $this->validate($command, $subject);

        $this->framework->persistSubject($subject);
    }
}
