<?php

namespace App\Handler\User;

use App\Command\User\DeleteFrameworkAclCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\UserDocAcl;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteFrameworkAclHandler
 *
 * @DI\Service()
 */
class DeleteFrameworkAclHandler extends BaseUserHandler
{
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
