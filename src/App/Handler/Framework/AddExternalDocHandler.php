<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddExternalDocCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddExternalDocHandler
 *
 * @DI\Service()
 */
class AddExternalDocHandler extends BaseDoctrineHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddExternalDocCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddExternalDocCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $doc = $command->getDoc();
        $identifier = $command->getIdentifier();
        $autoLoad = $command->getAutoload();
        $url = $command->getUrl();
        $title = $command->getTitle();

        $doc->addExternalDoc($identifier, $autoLoad, $url, $title);
        $this->em->persist($doc);

//        $dispatcher->dispatch(AddExternalDocEvent::class, new AddExternalDocEvent());
    }
}
