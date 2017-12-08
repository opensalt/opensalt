<?php

namespace App\Handler\User;

use App\Command\User\DeleteFrameworkAclCommand;
use App\Event\CommandEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class DeleteFrameworkAclHandler
 *
 * @DI\Service()
 */
class DeleteFrameworkAclHandler
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
     * DeleteFrameworkAclHandler constructor.
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
     * @DI\Observe(App\Command\User\DeleteFrameworkAclCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteFrameworkAclCommand $command */
        $command = $event->getCommand();

        $errors = $this->validator->validate($command);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error adding framework ACL: {$errorString}");
        }

        $doc = $command->getDoc();
        $user = $command->getUser();

        $aclRepo = $this->em->getRepository('SaltUserBundle:UserDocAcl');
        $acl = $aclRepo->findByDocUser($doc, $user);
        if (null !== $acl) {
            $this->em->remove($acl);
        }

//        $dispatcher->dispatch(DeleteFrameworkAclEvent::class, new DeleteFrameworkAclEvent());
    }
}
