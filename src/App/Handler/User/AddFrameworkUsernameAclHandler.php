<?php

namespace App\Handler\User;

use App\Command\User\AddFrameworkUsernameAclCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Entity\UserDocAcl;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddFrameworkUsernameAclHandler
 *
 * @DI\Service()
 */
class AddFrameworkUsernameAclHandler extends BaseUserHandler
{
    /**
     * @DI\Observe(App\Command\User\AddFrameworkUsernameAclCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddFrameworkUsernameAclCommand $command */
        $command = $event->getCommand();

        $dto = $command->getDto();
        $this->validate($command, $dto);

        $username = $dto->username;
        $lsDoc = $dto->lsDoc;
        $access = $dto->access;

        $userRepo = $this->em->getRepository(User::class);
        /** @var User $user */
        $user = $userRepo->loadUserByUsername($username);
        if (null === $user) {
            throw new \InvalidArgumentException('Username does not exist');
        }

        if (UserDocAcl::DENY === $access) {
            if ($lsDoc->getOrg() !== $user->getOrg()) {
                throw new \InvalidArgumentException('Trying to deny access which is already denied by default');
            }
        } elseif (UserDocAcl::ALLOW === $access) {
            if ($lsDoc->getOrg() === $user->getOrg()) {
                throw new \InvalidArgumentException('Trying to allow access which is already allowed by default');
            }
        } else {
            throw new \InvalidArgumentException('Invalid access qualifier');
        }

        $acl = new UserDocAcl($user, $lsDoc, $access);
        $this->em->persist($acl);
    }
}
