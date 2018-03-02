<?php

namespace App\Handler\User;

use App\Command\User\AddUserByNameCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use App\Service\User\UserManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use App\Entity\User\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AddUserByNameHandler
 */
class AddUserByNameHandler extends BaseDoctrineHandler
{
    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry, UserManager $userManager)
    {
        parent::__construct($validator, $registry);
        $this->userManager = $userManager;
    }

    /**
     * @DI\Observe(App\Command\User\AddUserByNameCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
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
