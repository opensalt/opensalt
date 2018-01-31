<?php

namespace App\Handler\User;

use App\Command\User\UpdateOrganizationCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateOrganizationHandler
 *
 * @DI\Service()
 */
class UpdateOrganizationHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\UpdateOrganizationCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateOrganizationCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $organization = $command->getOrg();

        $this->em->persist($organization);

        $command->setNotificationEvent(new NotificationEvent(
            'O04',
            sprintf('Organization "%s" modified', $organization->getName()),
            null
        ));
    }
}
