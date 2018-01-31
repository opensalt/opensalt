<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteAssociationCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeleteAssociationHandler
 *
 * @DI\Service()
 */
class DeleteAssociationHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\DeleteAssociationCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteAssociationCommand $command */
        $command = $event->getCommand();

        $association = $command->getAssociation();
        $associationId = $association->getId();
        $associationIdentifier = $association->getIdentifier();


        $this->validate($command, $association);

        $doc = $association->getLsDoc();
        $this->framework->deleteAssociation($association);

        $fromTitle = $this->getTitle($association->getOrigin());
        $toTitle = $this->getTitle($association->getDestination());
        if (LsAssociation::EXEMPLAR === $association->getType()) {
            $notification = new NotificationEvent(
                'A04',
                sprintf('Exemplar (%s) removed from "%s"', $toTitle, $fromTitle),
                $doc,
                [
                    'assoc-d' => [
                        $associationId => $associationIdentifier,
                    ],
                ]
            );
        } else {
            $notification = new NotificationEvent(
                'A05',
                sprintf('"%s" association deleted from "%s" to "%s"', $association->getType(), $fromTitle, $toTitle),
                $doc,
                [
                    'assoc-d' => [
                        $associationId => $associationIdentifier,
                    ],
                ]
            );
        }
        $command->setNotificationEvent($notification);
    }

    protected function getTitle($obj): string
    {
        if (null === $obj) {
            return 'NONE';
        }

        if (is_string($obj)) {
            return $obj;
        }

        if ($obj instanceof LsItem || $obj instanceof LsDoc) {
            return $obj->getShortStatement();
        }

        return 'UNKNOWN';
    }
}
