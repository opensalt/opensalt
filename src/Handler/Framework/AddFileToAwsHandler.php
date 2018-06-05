<?php

namespace App\Handler\Framework;

use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Handler\BaseDoctrineHandler;
use App\Entity\Framework\AwsStorage;

class AddFileToAwsHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $command = $event->getCommand();
        $this->validate($command, $command);
        $lsItem = $command->getItem();
        $fileName = $command->getFileName();
        $field = $command->getField();
        $repo = $this->em->getRepository(AwsStorage::class);
        // $comment = $repo->addFile($lsItem, $fileName, $field);
        // $command->setComment($comment);
        // $notification = 'File Added';
        // $command->setNotificationEvent($notification);
    }
}
