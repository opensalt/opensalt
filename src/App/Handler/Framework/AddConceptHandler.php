<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddConceptCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddConceptHandler
 *
 * @DI\Service()
 */
class AddConceptHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddConceptCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddConceptCommand $command */
        $command = $event->getCommand();

        $concept = $command->getConcept();
        $this->validate($command, $concept);

        $this->framework->persistConcept($concept);

//        $dispatcher->dispatch(AddConceptEvent::class, new AddConceptEvent());
    }
}
