<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddAssociationGroupCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddAssociationGroupHandler
 *
 * @DI\Service()
 */
class AddAssociationGroupHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddAssociationGroupCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddAssociationGroupCommand $command */
        $command = $event->getCommand();

        $associationGroup = $command->getAssociationGroup();
        $this->validate($command, $associationGroup);

        $this->framework->persistAssociationGroup($associationGroup);

        $notification = new NotificationEvent(
            'G01',
            sprintf('Association Group "%s" added', $associationGroup->getTitle()),
            $associationGroup->getLsDoc(),
            [
                'assocGrp-a' => [
                    $associationGroup,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
