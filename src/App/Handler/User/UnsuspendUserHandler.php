<?php

namespace App\Handler\User;

use App\Command\User\UnsuspendUserCommand;
use App\Event\CommandEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UnsuspendUserHandler
 *
 * @DI\Service()
 */
class UnsuspendUserHandler
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * UnsuspendUserHandler constructor.
     *
     * @DI\InjectParams({
     *     "validator" = @DI\Inject("validator"),
     *     "registry" = @DI\Inject("doctrine")
     * })
     *
     * @param ValidatorInterface $validator
     * @param ManagerRegistry $registry
     */
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry)
    {
        $this->validator = $validator;
        $this->em = $registry->getManager();
    }

    /**
     * @DI\Observe(App\Command\User\UnsuspendUserCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UnsuspendUserCommand $command */
        $command = $event->getCommand();

        $errors = $this->validator->validate($command);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error suspending user: {$errorString}");
        }

        $user = $command->getUser();
        $user->unsuspendUser();

//        $dispatcher->dispatch(UnsuspendUserEvent::class, new UnsuspendUserEvent());
    }
}
