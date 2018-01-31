<?php

namespace App\Handler\User;

use App\Command\User\RemoveUserRoleCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RemoveUserRoleHandler
 *
 * @DI\Service()
 */
class RemoveUserRoleHandler extends BaseDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry)
    {
        parent::__construct($validator, $registry);
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

        $this->em->getRepository(User::class)->removeRoleFromUser($username, $role);
    }
}
