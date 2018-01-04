<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteItemWithChildrenCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteItemWithChildrenHandler
 *
 * @DI\Service()
 */
class DeleteItemWithChildrenHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\DeleteItemWithChildrenCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteItemWithChildrenCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        //$hasChildren = $item->getChildren();

        $this->validate($command, $item);

        $this->framework->deleteItemWithChildren($item);

//        $dispatcher->dispatch(DeleteItemWithChildrenEvent::class, new DeleteItemWithChildrenEvent());
    }
}
