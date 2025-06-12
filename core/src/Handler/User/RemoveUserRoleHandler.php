<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\RemoveUserRoleCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use App\Service\User\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RemoveUserRoleHandler extends BaseDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, private readonly UserManager $userManager)
    {
        parent::__construct($validator, $entityManager);
    }

    #[\Override]
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
