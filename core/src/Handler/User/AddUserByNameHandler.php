<?php

namespace App\Handler\User;

use App\Command\User\AddUserByNameCommand;
use App\Entity\User\User;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use App\Service\User\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddUserByNameHandler extends BaseDoctrineHandler
{
    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, UserManager $userManager)
    {
        parent::__construct($validator, $entityManager);
        $this->userManager = $userManager;
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddUserByNameCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $userName = $command->getUserName();
        $org = $command->getOrganization();
        $plainPassword = $command->getPlainPassword();
        $role = $command->getRole();

        $newPassword = $this->userManager->addNewUser($userName, $org, $plainPassword, $role, User::ACTIVE);

        $command->setNewPassword($newPassword);

        $command->setNotificationEvent(new NotificationEvent(
            'U02',
            sprintf('User "%s" added to "%s"', $userName, $org->getName()),
            null
        ));
    }
}
