<?php

namespace App\Handler\User;

use App\Command\User\ApprovedUserCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ApprovedUserHandler
 *
 * @DI\Service()
 */
class ApprovedUserHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\ApprovedUserCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ApprovedUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);
        $user = $command->getUser();
        $user->ApprovedUser();

    }
}

