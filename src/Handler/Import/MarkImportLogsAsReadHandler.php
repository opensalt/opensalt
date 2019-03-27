<?php

namespace App\Handler\Import;

use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Command\Import\MarkImportLogsAsReadCommand;
use App\Event\CommandEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MarkImportLogsAsReadHandler extends AbstractDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        parent::__construct($validator, $entityManager);
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var MarkImportLogsAsReadCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $doc = $command->getDoc();
        foreach ($doc->getImportLogs() as $log) {
            $log->markAsRead();
        }

        $notification = new NotificationEvent(
            'D14',
            sprintf('Import logs of "%s" marked as read', $doc->getTitle()),
            $doc,
            [
            ],
            false
        );
        $command->setNotificationEvent($notification);
    }
}
