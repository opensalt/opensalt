<?php

namespace App\Handler\User;

use App\Command\User\RemoveUserRoleCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use App\Service\User\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RemoveUserRoleHandler extends BaseDoctrineHandler
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

    /**
     * @DI\Observe(App\Command\User\RemoveUserRoleCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var RemoveUserRoleCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $username = $command->getUserName();
        $role = $command->getRole();

        $this->userManager->removeRoleFromUser($username, $role);
    }
}
