<?php

namespace App\Handler\Framework;

use App\Command\Framework\CopyFrameworkCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use App\Entity\Framework\LsDoc;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CopyFrameworkHandler extends BaseDoctrineHandler
{
    /**
     * @DI\Observe(App\Command\Framework\CopyFrameworkCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
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
