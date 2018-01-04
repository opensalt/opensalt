<?php

namespace App\Handler\User;

use App\Command\User\DeleteUserCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteUserHandler
 *
 * @DI\Service()
 */
class DeleteUserHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\DeleteUserCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();

        $this->em->remove($user);

//        $dispatcher->dispatch(DeleteUserEvent::class, new DeleteUserEvent());
    }
}
