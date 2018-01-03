<?php

namespace App\Handler\User;

use App\Command\User\UnsuspendUserCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UnsuspendUserHandler
 *
 * @DI\Service()
 */
class UnsuspendUserHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\UnsuspendUserCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UnsuspendUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();
        $user->unsuspendUser();

//        $dispatcher->dispatch(UnsuspendUserEvent::class, new UnsuspendUserEvent());
    }
}
