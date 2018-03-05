<?php

namespace App\Handler\Import;

use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Command\Import\ParseCsvGithubDocumentCommand;
use App\Event\CommandEvent;
use App\Entity\Framework\LsDoc;
use App\Service\GithubImport;
use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ParseCsvGithubDocumentHandler extends AbstractDoctrineHandler
{
    /**
     * @var GithubImport
     */
    protected $importService;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, GithubImport $importService)
    {
        parent::__construct($validator, $entityManager);
        $this->importService = $importService;
    }

    /**
     * @DI\Observe(App\Command\Import\ParseCsvGithubDocumentCommand::class)
     */
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

        $doc = $this->em->getRepository(LsDoc::class)->find($docId);

        $notification = new NotificationEvent(
            'D15',
            sprintf('Framework "%s" updated from GitHub CSV', $doc->getTitle()),
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
