<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddTreeAssociationCommand;
use App\Entity\Framework\AssociationSubtype;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Service\FrameworkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddTreeAssociationHandler extends BaseFrameworkHandler
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(ValidatorInterface $validator, FrameworkService $framework, EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct($validator, $framework);
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddTreeAssociationCommand $command */
        $command = $event->getCommand();

        $allowedSubtypes = $this->em->getRepository(AssociationSubtype::class)->findAll();
        $command->setAllowedSubtypes($allowedSubtypes);

        $this->validate($command, $command);

        $doc = $command->getDoc();
        $type = $command->getType();
        $origin = $command->getOrigin();
        $dest = $command->getDestination();
        $assocGroup = $command->getAssocGroup();
        $annotation = $command->getAnnotation();

        $association = $this->framework->addTreeAssociation($doc, $origin, $type, $dest, $assocGroup, $annotation);
        $command->setAssociation($association);

        $fromTitle = $this->getTitle($association->getOrigin());
        $toTitle = $this->getTitle($association->getDestination());
        $notification = new NotificationEvent(
            'A03',
            sprintf('"%s" association added from "%s" to "%s"', $association->getType(), $fromTitle, $toTitle),
            $association->getLsDoc(),
            [
                'assoc-a' => [
                    $association,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }

    protected function getTitle($obj): string
    {
        if (null === $obj) {
            return 'NONE';
        }

        if (\is_string($obj)) {
            return $obj;
        }

        if ($obj instanceof LsItem || $obj instanceof LsDoc) {
            return $obj->getShortStatement();
        }

        return 'UNKNOWN';
    }
}
