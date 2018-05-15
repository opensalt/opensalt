<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddFileToAwsCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Handler\BaseDoctrineHandler;
use App\Entity\Framework\AwsStorage;

class UpdateAttachmentHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $command = $event->getCommand();
        $this->validate($command, $command);

        $lsItem = $command->getItem();
        $fileName = $command->getFileName();
        $repo = $this->em->getRepository(AwsStorage::class);

        $comment = $repo->updateFile($lsItem, $fileName);

//        $command->setComment($comment);

        $notification="File uodated";

//        $command->setNotificationEvent($notification);
    }
}

