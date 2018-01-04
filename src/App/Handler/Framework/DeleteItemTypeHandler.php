<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteItemTypeCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteItemTypeHandler
 *
 * @DI\Service()
 */
class DeleteItemTypeHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\DeleteItemTypeCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteItemTypeCommand $command */
        $command = $event->getCommand();

        $itemType = $command->getItemType();
        $this->validate($command, $itemType);

        $this->framework->deleteItemType($itemType);

//        $dispatcher->dispatch(DeleteItemTypeEvent::class, new DeleteItemTypeEvent());
    }
}
