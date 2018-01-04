<?php

namespace App\Handler\User;

use App\Command\User\AddUserByNameCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AddUserByNameHandler
 *
 * @DI\Service()
 */
class AddUserByNameHandler extends BaseDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry)
    {
        parent::__construct($validator, $registry);
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

        $newPassword = $this->em->getRepository(User::class)->addNewUser($userName, $org, $plainPassword, $role);

        $command->setNewPassword($newPassword);

//        $dispatcher->dispatch(AddUserByNameEvent::class, new AddUserByNameEvent());
    }
}
