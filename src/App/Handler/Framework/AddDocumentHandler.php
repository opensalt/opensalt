<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddDocumentCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddDocumentHandler
 *
 * @DI\Service()
 */
class AddDocumentHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddDocumentCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddDocumentCommand $command */
        $command = $event->getCommand();

        $doc = $command->getDoc();
        $this->validate($command, $doc);

        $this->framework->addDocument($doc);

//        $dispatcher->dispatch(AddDocumentEvent::class, new AddDocumentEvent());
    }
}
