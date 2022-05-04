<?php

namespace App\Handler\User;

use App\Command\User\AddFrameworkUsernameAclCommand;
use App\Entity\User\User;
use App\Entity\User\UserDocAcl;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddFrameworkUsernameAclHandler extends BaseUserHandler
{
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
        $user = $userRepo->loadUserByIdentifier($username);
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
