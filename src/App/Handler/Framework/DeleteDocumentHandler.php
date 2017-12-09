<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteDocumentCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteDocumentHandler
 *
 * @DI\Service()
 */
class DeleteDocumentHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\DeleteDocumentCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteDocumentCommand $command */
        $command = $event->getCommand();

        $doc = $command->getDoc();
        $this->validate($command, $doc);

        $this->framework->deleteFramework($doc, $command->getProgressCallback());

//        $dispatcher->dispatch(DeleteDocumentEvent::class, new DeleteDocumentEvent());
    }
}
