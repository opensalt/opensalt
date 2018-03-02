<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteAssociationGroupHandler
 *
 * @DI\Service()
 */
class DeleteAssociationGroupHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\DeleteAssociationGroupCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteAssociationGroupCommand $command */
        $command = $event->getCommand();

        $associationGroup = $command->getAssociationGroup();
        $this->validate($command, $associationGroup);

        $this->framework->deleteAssociationGroup($associationGroup);

        $notification = new NotificationEvent(
            'G02',
            sprintf('Association Group "%s" deleted', $associationGroup->getTitle()),
            $associationGroup->getLsDoc(),
            [
                'assocGrp-d' => [
                    $associationGroup->getId() => $associationGroup->getIdentifier(),
                ]
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
