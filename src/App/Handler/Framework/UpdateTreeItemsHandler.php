<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateTreeItemsCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateTreeItemsHandler
 *
 * @DI\Service()
 */
class UpdateTreeItemsHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\UpdateTreeItemsCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateTreeItemsCommand $command */
        $command = $event->getCommand();

        $this->validate($command, $command);

        $doc = $command->getDoc();
        $items = $command->getItems();

        $ret = $this->framework->updateTreeItems($doc, $items);
        $command->setReturnValues($ret['return']);

        $changes = $ret['changes'];

        $notification = new NotificationEvent(
            'D08',
            'Framework tree updated',
            $doc,
            $changes
        );
        $command->setNotificationEvent($notification);
    }
}
