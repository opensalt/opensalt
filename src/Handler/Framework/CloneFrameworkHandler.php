<?php

namespace App\Handler\Framework;

use App\Command\Framework\CopyFrameworkCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use App\Entity\Framework\LsDoc;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CloneFrameworkHandler extends BaseDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        parent::__construct($validator, $entityManager);
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var CopyFrameworkCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        /** @var LsDoc $doc */
        $doc = $command->getDoc();

        $newDoc = clone $doc;
        $newDoc->setTitle('Clone '.$newDoc->getTitle());
        $this->em->persist($newDoc);
    }
}
