<?php

namespace App\Handler\User;

use App\Command\User\SetUserPasswordCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SetUserPasswordHandler
 *
 * @DI\Service()
 */
class SetUserPasswordHandler extends BaseDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry)
    {
        parent::__construct($validator, $registry);
    }

    /**
     * @DI\Observe(App\Command\User\SetUserPasswordCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var SetUserPasswordCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $username = $command->getUserName();
        $plainPassword = $command->getPlainPassword();

        $newPassword = $this->em->getRepository(User::class)->setUserPassword($username, $plainPassword);

        $command->setPlainPassword($newPassword);

//        $dispatcher->dispatch(SetUserPasswordEvent::class, new SetUserPasswordEvent());
    }
}
