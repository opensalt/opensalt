<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateAssociationCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateAssociationHandler
 *
 * @DI\Service()
 */
class UpdateAssociationHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\UpdateAssociationCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateAssociationCommand $command */
        $command = $event->getCommand();

        $association = $command->getAssociation();
        $this->validate($command, $association);

        $notification = new NotificationEvent(
            'A07',
            sprintf('Association "%s" modified', $association->getIdentifier()),
            $association->getLsDoc(),
            [
                'assoc-u' => [
                    $association->getId() => $association->getIdentifier(),
                ]
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
