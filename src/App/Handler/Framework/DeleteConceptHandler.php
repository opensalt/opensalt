<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteConceptCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteConceptHandler
 *
 * @DI\Service()
 */
class DeleteConceptHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\DeleteConceptCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteConceptCommand $command */
        $command = $event->getCommand();

        $concept = $command->getConcept();
        $this->validate($command, $concept);

        $this->framework->deleteConcept($concept);

//        $dispatcher->dispatch(DeleteConceptEvent::class, new DeleteConceptEvent());
    }
}
