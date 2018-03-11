<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddExternalDocCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddExternalDocHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddExternalDocCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $doc = $command->getDoc();
        $identifier = $command->getIdentifier();
        $autoLoad = $command->getAutoload();
        $url = $command->getUrl();
        $title = $command->getTitle();

        $doc->addExternalDoc($identifier, $autoLoad, $url, $title);
        $this->em->persist($doc);

        $notification = new NotificationEvent(
            'D02',
            sprintf('Framework "%s" modified', $doc->getTitle()),
            $doc,
            [
                'doc-u' => [
                    $doc,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
