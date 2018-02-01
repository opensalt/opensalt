<?php

namespace App\Handler\User;

use App\Command\User\AddUserCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddUserHandler
 *
 * @DI\Service()
 */
class AddUserHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\AddUserCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddUserCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $user = $command->getUser();

        $encryptedPassword = $command->getEncryptedPassword();
        $user->setPassword($encryptedPassword);

        $this->em->persist($user);

        $command->setNotificationEvent(new NotificationEvent(
            'U01',
            sprintf('User "%s" added to "%s"', $user->getUsername(), $user->getOrg()->getName()),
            null
        ));
    }
}
