<?php

namespace App\Handler\User;

use App\Command\User\AddOrganizationByNameCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\Organization;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AddOrganizationByNameHandler
 *
 * @DI\Service()
 */
class AddOrganizationByNameHandler extends BaseDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry)
    {
        parent::__construct($validator, $registry);
    }

    /**
     * @DI\Observe(App\Command\User\AddOrganizationByNameCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddOrganizationByNameCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $organizationName = $command->getOrganizationName();

        $this->em->getRepository(Organization::class)->addNewOrganization($organizationName);

        $command->setNotificationEvent(new NotificationEvent(
            'O02',
            sprintf('Organization "%s" added', $organizationName),
            null
        ));
    }
}
