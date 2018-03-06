<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateDocumentCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateDocumentHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateDocumentCommand $command */
        $command = $event->getCommand();

        $doc = $command->getDoc();
        $this->validate($command, $doc);

        $doc->setUpdatedAt(new \DateTime());

        $this->framework->unlockObject($doc);

        /* @todo Check explicitly for change in publication status for a different notification */

        $notification = new NotificationEvent(
            'D06',
           'Framework document modified',
           $doc,
           [
               'doc-u' => [
                   $doc->getId() => $doc->getIdentifier(),
               ],
               'doc-ul' => [
                   $doc->getId() => $doc->getIdentifier(),
               ],
           ]
        );
        $command->setNotificationEvent($notification);
    }
}
