<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeriveDocumentCommand;
use App\Entity\Framework\LsDoc;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\BaseDoctrineHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeriveDocumentHandler extends BaseDoctrineHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeriveDocumentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $doc = $command->getDoc();
        //$fileContent = $command->getFileContent();
        //$frameworkToAssociate = $command->getFrameworkToAssociate();

        $derivativeDoc = $this->em->getRepository(LsDoc::class)
            ->makeDerivative($doc);

        foreach ($doc->getTopLsItems() as $oldTopItem) {
            $newItem = $oldTopItem->copyToLsDoc($derivativeDoc);
            $this->em->persist($newItem);

            $derivativeDoc->addTopLsItem($newItem);
        }

        $command->setDerivedDoc($derivativeDoc);

        $notification = new NotificationEvent(
            'D03',
            sprintf('Derived framework "%s" added', $derivativeDoc->getTitle()),
            $derivativeDoc,
            [
                'doc-a' => [
                    $derivativeDoc,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
