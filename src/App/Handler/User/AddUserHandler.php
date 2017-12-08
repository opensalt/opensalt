<?php

namespace App\Handler\User;

use App\Command\User\AddUserCommand;
use App\Event\CommandEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AddUserHandler
 *
 * @DI\Service()
 */
class AddUserHandler
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
     * AddUserHandler constructor.
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
     * @DI\Observe(App\Command\User\AddUserCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddUserCommand $command */
        $command = $event->getCommand();

        $errors = $this->validator->validate($command);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error adding user: {$errorString}");
        }

        $user = $command->getUser();

        $encryptedPassword = $command->getEncryptedPassword();
        $user->setPassword($encryptedPassword);

        $this->em->persist($user);

//        $dispatcher->dispatch(AddUserEvent::class, new AddUserEvent());
    }
}
