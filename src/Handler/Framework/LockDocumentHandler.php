<?php

namespace App\Handler\Framework;

use App\Command\Framework\LockDocumentCommand;
use App\Command\Framework\UnlockDocumentCommand;
use App\Entity\NotificationOnlyChangeEntry;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LockDocumentHandler
 *
 * @DI\Service()
 */
class LockDocumentHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\LockDocumentCommand::class)
     * @DI\Observe(App\Command\Framework\UnlockDocumentCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var LockDocumentCommand|UnlockDocumentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        if ($command instanceof LockDocumentCommand) {
            $this->lockDocument($command);
        }

        if ($command instanceof UnlockDocumentCommand) {
            $this->unlockDocument($command);
        }
    }

    protected function lockDocument(LockDocumentCommand $command): void
    {
        $doc = $command->getDoc();
        $user = $command->getUser();

        $this->framework->lockObject($doc, $user);

        $notification = new NotificationEvent(
            'D04',
            sprintf('Document "%s" is being edited', $doc->getTitle()),
            $doc,
            [
                'doc-l' => [
                    $doc,
                ],
            ]
        );
        $notification->setUsername($user->getUsername());
        $command->setNotificationEvent($notification);

        $command->setChangeEntry(new NotificationOnlyChangeEntry($doc, $user, 'Dont Log - Document locked'));
    }

    protected function unlockDocument(UnlockDocumentCommand $command): void
    {
        $doc = $command->getDoc();
        $user = $command->getUser();

        $this->framework->unlockObject($doc, $user);

        $notification = new NotificationEvent(
            'D05',
            sprintf('Document "%s" is no longer being edited', $doc->getTitle()),
            $doc,
            [
                'doc-ul' => [
                    $doc,
                ],
            ]
        );
        $command->setNotificationEvent($notification);

        $command->setChangeEntry(new NotificationOnlyChangeEntry($doc, $user, 'Dont Log - Document unlocked'));
    }
}
