<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddItemCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddItemHandler
 *
 * @DI\Service()
 */
class AddItemHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddItemCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddItemCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();

        if ($command->getParent()) {
            $command->getParent()->addChild($item, $command->getAssocGroup());
        } else {
            $command->getDoc()->addTopLsItem($item, $command->getAssocGroup());
        }

        $this->validate($command, $item);

        $this->framework->persistItem($item);

        $parent = $item->getParentItem();
        if (null === $parent) {
            $parentTitle = $item->getLsDoc()->getTitle();
        } else {
            $parentTitle = substr($parent->getShortStatement(), 0, 60);
        }
        $notification = new NotificationEvent(
            sprintf('"%s" added as a child of "%s"', $item->getShortStatement(), $parentTitle),
            $item->getLsDoc(),
            [
                'items' => [
                    $item->getId() => $item->getIdentifier(),
                    $parent ? $parent->getId() : '-' => $parent ? $parent->getIdentifier() : '-',
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
