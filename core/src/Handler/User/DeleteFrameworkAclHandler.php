<?php

namespace App\Handler\User;

use App\Command\User\DeleteFrameworkAclCommand;
use App\Entity\User\UserDocAcl;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteFrameworkAclHandler extends BaseUserHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteFrameworkAclCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $doc = $command->getDoc();
        $user = $command->getUser();

        $aclRepo = $this->em->getRepository(UserDocAcl::class);
        $acl = $aclRepo->findByDocUser($doc, $user);
        if (null !== $acl) {
            $this->em->remove($acl);
        }
    }
}
