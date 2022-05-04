<?php

namespace App\Handler\Import;

use App\Command\Import\ParseCsvGithubDocumentCommand;
use App\Entity\Framework\LsDoc;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Service\GithubImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ParseCsvGithubDocumentHandler extends AbstractDoctrineHandler
{
    public function __construct(
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        private GithubImport $importService,
    ) {
        parent::__construct($validator, $entityManager);
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ParseCsvGithubDocumentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $itemKeys = $command->getItemKeys();
        $fileContent = $command->getFileContent();
        $docId = $command->getDocId();
        $frameworkToAssociate = $command->getFrameworkToAssociate();
        $missingFieldsLog = $command->getMissingFieldsLog();

        $this->importService->parseCSVGithubDocument($itemKeys, $fileContent, $docId, $frameworkToAssociate, $missingFieldsLog);

        $doc = $this->em->getRepository(LsDoc::class)->find((int) $docId);

        $notification = new NotificationEvent(
            'D15',
            sprintf('Framework "%s" updated from GitHub CSV', $doc?->getTitle() ?? 'Not Found'),
            $doc,
            [
                'doc-u' => [
                    $doc,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
