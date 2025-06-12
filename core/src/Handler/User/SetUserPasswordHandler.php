<?php

declare(strict_types=1);

namespace App\Handler\User;

use App\Command\User\SetUserPasswordCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use App\Service\User\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SetUserPasswordHandler extends BaseDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, private readonly UserManager $userManager)
    {
        parent::__construct($validator, $entityManager);
    }

    #[\Override]
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var SetUserPasswordCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $username = $command->getUsername();
        $plainPassword = $command->getPlainPassword();

        $newPassword = $this->userManager->setUserPassword($username, $plainPassword);

        $command->setPlainPassword($newPassword);

        $command->setNotificationEvent(new NotificationEvent(
            'U08',
            sprintf('Password set for "%s"', $username),
            null
        ));
    }
}
