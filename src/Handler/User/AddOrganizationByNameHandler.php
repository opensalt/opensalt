<?php

namespace App\Handler\User;

use App\Command\User\AddOrganizationByNameCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use App\Entity\User\Organization;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddOrganizationByNameHandler extends BaseDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        parent::__construct($validator, $entityManager);
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
