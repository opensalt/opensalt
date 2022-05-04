<?php

namespace App\Handler\Framework;

use App\Command\Framework\CloneFrameworkCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CloneFrameworkHandler extends BaseDoctrineHandler
{
    private $repository;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, LsDocRepository $repository)
    {
        $this->repository = $repository;
        parent::__construct($validator, $entityManager);
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var CloneFrameworkCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        /** @var LsDoc $doc */
        $doc = $command->getDoc();

        $newDoc = clone $doc;
        $newDoc->setTitle('Clone '.$newDoc->getTitle());
        $newDoc->setFrameworkType(null);

        $this->repository->copyDocumentContentToDoc($doc, $newDoc, false);
        $this->em->persist($newDoc);
        $this->em->flush();
        $notification = new NotificationEvent(
            'D16',
            sprintf('Clone of framework "%s" added', $newDoc->getTitle()),
            $newDoc,
            [
                'doc-a' => [
                    $newDoc,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
