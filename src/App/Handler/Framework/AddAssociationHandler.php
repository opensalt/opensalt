<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddAssociationCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddAssociationHandler
 *
 * @DI\Service()
 */
class AddAssociationHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddAssociationCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddAssociationCommand $command */
        $command = $event->getCommand();

        $association = $command->getAssociation();
        $this->validate($command, $association);

        $this->framework->persistAssociation($association);

//        $dispatcher->dispatch(AddAssociationEvent::class, new AddAssociationEvent());
    }
}
