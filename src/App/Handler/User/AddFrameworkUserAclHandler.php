<?php

namespace App\Handler\User;

use App\Command\User\AddFrameworkUserAclCommand;
use App\Event\CommandEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\UserDocAcl;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AddFrameworkUserAclHandler
 *
 * @DI\Service()
 */
class AddFrameworkUserAclHandler
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
     * AddFrameworkUserAclHandler constructor.
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
     * @DI\Observe(App\Command\User\AddFrameworkUserAclCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddFrameworkUserAclCommand $command */
        $command = $event->getCommand();

        $dto = $command->getDto();

        $errors = $this->validator->validate($dto);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error adding framework ACL: {$errorString}");
        }

        $user = $dto->user;
        $lsDoc = $dto->lsDoc;
        $access = $dto->access;

        if (UserDocAcl::DENY === $access) {
            if ($lsDoc->getOrg() !== $user->getOrg()) {
                throw new \InvalidArgumentException('Trying to deny access which is already denied');
            }
        } elseif (UserDocAcl::ALLOW === $access) {
            if ($lsDoc->getOrg() === $user->getOrg()) {
                throw new \InvalidArgumentException('Trying to allow access which is already allowed');
            }
        } else {
            throw new \InvalidArgumentException('Invalid access qualifier');
        }

        $acl = new UserDocAcl($user, $lsDoc, $access);
        $this->em->persist($acl);

//        $dispatcher->dispatch(AddFrameworkUserAclEvent::class, new AddFrameworkUserAclEvent());
    }
}
