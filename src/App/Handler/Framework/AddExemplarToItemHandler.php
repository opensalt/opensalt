<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddExemplarToItemCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddExemplarToItemHandler
 *
 * @DI\Service()
 */
class AddExemplarToItemHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddExemplarToItemCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddExemplarToItemCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        $url = $command->getUrl();

        $this->validate($command, $item);

        $association = $this->framework->addExemplarToItem($item, $url);
        $command->setAssociation($association);

//        $dispatcher->dispatch(AddExemplarToItemEvent::class, new AddExemplarToItemEvent());
    }
}
