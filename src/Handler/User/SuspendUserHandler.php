<?php

namespace App\Handler\User;

use App\Command\User\SuspendUserCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SuspendUserHandler
 *
 * @DI\Service()
 */
class SuspendUserHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\SuspendUserCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var SuspendUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();
        $user->suspendUser();

        $command->setNotificationEvent(new NotificationEvent(
            'U06',
            sprintf('User "%s" suspended', $user->getUsername()),
            null
        ));
    }
}
