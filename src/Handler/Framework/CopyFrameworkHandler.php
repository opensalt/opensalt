<?php

namespace App\Handler\Framework;

use App\Command\Framework\CopyFrameworkCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use App\Entity\Framework\LsDoc;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CopyFrameworkHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var CopyFrameworkCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $copyType = $command->getCopyType();
        $fromDoc = $command->getFromDoc();
        $toDoc = $command->getToDoc();

        $this->em->getRepository(LsDoc::class)
            ->copyDocumentContentToDoc($fromDoc, $toDoc, 'copyAndAssociate' === $copyType);

    }
}
