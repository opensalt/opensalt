<?php

namespace App\Handler\Framework;

use App\Command\Framework\RemoveChildCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use CftfBundle\Entity\LsItem;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class RemoveChildHandler
 *
 * @DI\Service()
 */
class RemoveChildHandler extends BaseDoctrineHandler
{
    /**
     * @DI\Observe(App\Command\Framework\RemoveChildCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var RemoveChildCommand $command */
        $command = $event->getCommand();

        $parent = $command->getParent();
        $child = $command->getChild();

        $lsItemRepo = $this->em->getRepository(LsItem::class);
        $lsItemRepo->removeChild($parent, $child);

//        $dispatcher->dispatch(RemoveChildEvent::class, new RemoveChildEvent());
    }
}
