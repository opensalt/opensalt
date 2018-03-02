<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateItemTypeCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateItemTypeHandler
 *
 * @DI\Service()
 */
class UpdateItemTypeHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\UpdateItemTypeCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateItemTypeCommand $command */
        $command = $event->getCommand();

        $itemType = $command->getItemType();
        $this->validate($command, $itemType);
    }
}
