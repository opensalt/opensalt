<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateItemCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateItemHandler
 *
 * @DI\Service()
 */
class UpdateItemHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\UpdateItemCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateItemCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        $this->validate($command, $item);

        $item->setUpdatedAt(new \DateTime()); // Timestampable does not follow up the chain

//        $dispatcher->dispatch(UpdateItemEvent::class, new UpdateItemEvent());
    }
}
