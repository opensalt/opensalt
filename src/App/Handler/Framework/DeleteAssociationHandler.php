<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteAssociationCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteAssociationHandler
 *
 * @DI\Service()
 */
class DeleteAssociationHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\DeleteAssociationCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteAssociationCommand $command */
        $command = $event->getCommand();

        $association = $command->getAssociation();
        $this->validate($command, $association);

        $this->framework->deleteAssociation($association);

//        $dispatcher->dispatch(DeleteAssociationEvent::class, new DeleteAssociationEvent());
    }
}
