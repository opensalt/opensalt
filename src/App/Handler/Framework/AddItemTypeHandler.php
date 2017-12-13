<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddItemTypeCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddItemTypeHandler
 *
 * @DI\Service()
 */
class AddItemTypeHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddItemTypeCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddItemTypeCommand $command */
        $command = $event->getCommand();

        $itemType = $command->getItemType();
        $this->validate($command, $itemType);

        $this->framework->persistItemType($itemType);

//        $dispatcher->dispatch(AddItemTypeEvent::class, new AddItemTypeEvent());
    }
}
