<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateConceptCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateConceptHandler
 *
 * @DI\Service()
 */
class UpdateConceptHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\UpdateConceptCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateConceptCommand $command */
        $command = $event->getCommand();

        $concept = $command->getConcept();
        $this->validate($command, $concept);
    }
}
