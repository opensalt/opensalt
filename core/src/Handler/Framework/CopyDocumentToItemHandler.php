<?php

namespace App\Handler\Framework;

use App\Command\Framework\CopyDocumentToItemCommand;
use App\Entity\Framework\LsDoc;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CopyDocumentToItemHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var CopyDocumentToItemCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $fromDoc = $command->getFromDoc();
        $toDoc = $command->getToDoc();
        $progressCallback = $command->getCallback();

        $this->em->getRepository(LsDoc::class)->copyDocumentToItem($fromDoc, $toDoc, $progressCallback);
    }
}
