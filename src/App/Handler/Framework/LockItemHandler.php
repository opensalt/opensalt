<?php

namespace App\Handler\Framework;

use App\Command\Framework\LockItemCommand;
use App\Command\Framework\UnlockItemCommand;
use App\Entity\NotificationOnlyChangeEntry;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LockItemHandler
 *
 * @DI\Service()
 */
class LockItemHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\LockItemCommand::class)
     * @DI\Observe(App\Command\Framework\UnlockItemCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var LockItemCommand|UnlockItemCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        if ($command instanceof LockItemCommand) {
            $this->lockItem($command);
        }

        if ($command instanceof UnlockItemCommand) {
            $this->unlockItem($command);
        }
    }

    protected function lockItem(LockItemCommand $command): void
    {
        $item = $command->getItem();
        $user = $command->getUser();

        $this->framework->lockObject($item, $user);

        $notification = new NotificationEvent(
            'I06',
            sprintf('Item "%s" is being edited', $item->getShortStatement()),
            $item->getLsDoc(),
            [
                'item-l' => [
                    $item,
                ],
            ]
        );
        $command->setNotificationEvent($notification);

        $command->setChangeEntry(new NotificationOnlyChangeEntry($item->getLsDoc(), $user, 'Dont log - Item locked'));
    }

    protected function unlockItem(UnlockItemCommand $command): void
    {
        $item = $command->getItem();
        $user = $command->getUser();

        $this->framework->unlockObject($item, $user);

        $notification = new NotificationEvent(
            'I07',
            sprintf('Item "%s" is no longer being edited', $item->getShortStatement()),
            $item->getLsDoc(),
            [
                'item-ul' => [
                    $item,
                ],
            ]
        );
        $command->setNotificationEvent($notification);

        $command->setChangeEntry(new NotificationOnlyChangeEntry($item->getLsDoc(), $user, 'Dont log - Item unlocked'));
    }
}
