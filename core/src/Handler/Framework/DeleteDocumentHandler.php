<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteDocumentCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteDocumentHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteDocumentCommand $command */
        $command = $event->getCommand();

        $doc = $command->getDoc();
        $this->validate($command, $doc);

        $this->framework->deleteFramework($doc, $command->getProgressCallback());

        /** @psalm-suppress InvalidArrayOffset */
        $notification = new NotificationEvent(
            'D09',
            sprintf('Framework "%s" deleted', $doc->getTitle()),
            $doc,
            [
                'doc-d' => [
                    $doc->getId() => $doc->getIdentifier(),
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
