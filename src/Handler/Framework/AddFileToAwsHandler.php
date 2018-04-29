<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddFileToAwsCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
//use App\Command\Framework\AwsStorage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\ORM\EntityRepository;
use App\Handler\BaseDoctrineHandler;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Framework\AwsStorage;

class AddFileToAwsHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $command = $event->getCommand();
        $this->validate($command, $command);

        $lsItem = $command->getItem();

        $fileName = $command->getFileName();
$repo = $this->em->getRepository(AwsStorage::class);
        //$repo = $this->getEntityManager()->getRepository(AwsStorage::class);

        $comment = $repo->addFile($lsItem, $fileName);

//        $command->setComment($comment);

        $notification="File Added";

//        $command->setNotificationEvent($notification);
    }
}

