<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteAssociationGroupHandler
 *
 * @DI\Service()
 */
class DeleteAssociationGroupHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\DeleteAssociationGroupCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteAssociationGroupCommand $command */
        $command = $event->getCommand();

        $associationGroup = $command->getAssociationGroup();
        $this->validate($command, $associationGroup);

        $this->framework->deleteAssociationGroup($associationGroup);

//        $dispatcher->dispatch(DeleteAssociationGroupEvent::class, new DeleteAssociationGroupEvent());
    }
}
