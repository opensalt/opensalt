<?php

namespace App\Handler\Framework;

use App\Command\Framework\ChangeItemParentCommand;
use App\Event\CommandEvent;
use App\Handler\BaseDoctrineHandler;
use CftfBundle\Entity\LsAssociation;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ChangeItemParentHandler
 *
 * @DI\Service()
 */
class ChangeItemParentHandler extends BaseDoctrineHandler
{
    /**
     * @DI\Observe(App\Command\Framework\ChangeItemParentCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ChangeItemParentCommand $command */
        $command = $event->getCommand();

        $dto = $command->getDto();
        $this->validate($command, $dto);

        $dto->lsItem->setUpdatedAt(new \DateTime());
        $this->em->getRepository(LsAssociation::class)
            ->removeAllAssociationsOfType($dto->lsItem, LsAssociation::CHILD_OF);
        $dto->lsItem->addParent($dto->parentItem);

//        $dispatcher->dispatch(ChangeItemParentEvent::class, new ChangeItemParentEvent());
    }
}
