<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateFrameworkCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Service\FrameworkUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @deprecated
 */
class UpdateFrameworkHandler extends AbstractDoctrineHandler
{
    /**
     * @var FrameworkUpdater
     */
    private $frameworkUpdater;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, FrameworkUpdater $frameworkUpdater)
    {
        parent::__construct($validator, $entityManager);
        $this->frameworkUpdater = $frameworkUpdater;
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateFrameworkCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $doc = $command->getDoc();
        $fileContent = $command->getFileContent();
        $frameworkToAssociate = $command->getFrameworkToAssociate();
        $cfItemKeys = $command->getCfItemKeys();

        $this->frameworkUpdater->update($doc, $fileContent, $frameworkToAssociate, $cfItemKeys);

        /** @psalm-suppress InvalidArrayOffset */
        $notification = new NotificationEvent(
            'D07',
            'Framework document updated',
            $doc,
            [
                'doc-u' => [
                    $doc->getId() => $doc->getIdentifier(),
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
