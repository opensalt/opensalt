<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\AddAccessGroupByNameCommand;
use App\Entity\User\AccessGroup;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddAccessGroupByNameHandler extends BaseDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        parent::__construct($validator, $entityManager);
    }

    #[\Override]
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddAccessGroupByNameCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $organizationName = $command->getAccessGroupName();

        $this->em->getRepository(AccessGroup::class)->addNewAccessGroup($organizationName);

        $command->setNotificationEvent(new NotificationEvent(
            'O02',
            sprintf('Organization "%s" added', $organizationName),
            null
        ));
    }
}
