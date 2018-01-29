<?php

namespace App\Handler\User;

use App\Command\User\UnapprovedUserCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UnapprovedUserHandler
 *
 * @DI\Service()
 */
class UnapprovedUserHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\UnapprovedUserCommand::class)
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
        $user->unapprovedUser();
    }
}
