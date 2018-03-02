<?php

namespace App\Handler\User;

use App\Command\User\UpdateUserCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateUserHandler
 *
 * @DI\Service()
 */
class UpdateUserHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\UpdateUserCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();

        $this->em->persist($user);

        $command->setNotificationEvent(new NotificationEvent(
            'U04',
            sprintf('User "%s" modified', $user->getUsername()),
            null
        ));
    }
}
