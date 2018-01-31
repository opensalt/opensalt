<?php

namespace App\Handler\User;

use App\Command\User\AddOrganizationCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddOrganizationHandler
 *
 * @DI\Service()
 */
class AddOrganizationHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\AddOrganizationCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddOrganizationCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $organization = $command->getOrg();

        $this->em->persist($organization);

        $command->setNotificationEvent(new NotificationEvent(
            'O01',
            sprintf('Organization "%s" added', $organization->getName()),
            null
        ));
    }
}
