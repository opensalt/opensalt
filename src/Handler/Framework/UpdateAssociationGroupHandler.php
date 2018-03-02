<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateAssociationGroupCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateAssociationGroupHandler
 *
 * @DI\Service()
 */
class UpdateAssociationGroupHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\UpdateAssociationGroupCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateAssociationGroupCommand $command */
        $command = $event->getCommand();

        $associationGroup = $command->getAssociationGrouping();
        $this->validate($command, $associationGroup);

        $associationGroup->setUpdatedAt(new \DateTime());

        $notification = new NotificationEvent(
            'G03',
            sprintf('Association Group "%s" modified', $associationGroup->getTitle()),
            $associationGroup->getLsDoc(),
            [
                'assocGrp-u' => [
                    $associationGroup->getId() => $associationGroup->getIdentifier(),
                ]
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
