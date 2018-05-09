<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteFileCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Handler\BaseDoctrineHandler;
use App\Entity\Framework\AwsStorage;

class DeleteFileHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $command = $event->getCommand();
        //$this->validate($command, $command);

        $lsItem = $command->getItem();
        $fileName = $command->getFileName();
        $repo = $this->em->getRepository(AwsStorage::class);
        $comment = $repo->DeleteFile($lsItem, $fileName);
//        $command->setComment($comment);
        $notification="File Deleted";
//        $command->setNotificationEvent($notification);
    }
}

