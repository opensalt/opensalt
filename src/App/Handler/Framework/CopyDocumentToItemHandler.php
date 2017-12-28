<?php

namespace App\Handler\Framework;

use App\Command\Framework\CopyDocumentToItemCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use CftfBundle\Entity\LsDoc;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class CopyDocumentToItemHandler
 *
 * @DI\Service()
 */
class CopyDocumentToItemHandler extends BaseDoctrineHandler
{
    /**
     * @DI\Observe(App\Command\Framework\CopyDocumentToItemCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
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
