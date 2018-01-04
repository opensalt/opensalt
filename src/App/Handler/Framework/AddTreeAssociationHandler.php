<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddTreeAssociationCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddTreeAssociationHandler
 *
 * @DI\Service()
 */
class AddTreeAssociationHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddTreeAssociationCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddTreeAssociationCommand $command */
        $command = $event->getCommand();

        $this->validate($command, $command);

        $doc = $command->getDoc();
        $type = $command->getType();
        $origin = $command->getOrigin();
        $dest = $command->getDestination();
        $assocGroup = $command->getAssocGroup();

        $association = $this->framework->addTreeAssociation($doc, $origin, $type, $dest, $assocGroup);
        $command->setAssociation($association);

//        $dispatcher->dispatch(AddTreeAssociationEvent::class, new AddTreeAssociationEvent());
    }
}
