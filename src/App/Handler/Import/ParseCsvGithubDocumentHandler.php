<?php

namespace App\Handler\Import;

use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Command\Import\ParseCsvGithubDocumentCommand;
use App\Event\CommandEvent;
use CftfBundle\Entity\LsDoc;
use Doctrine\Common\Persistence\ManagerRegistry;
use GithubFilesBundle\Service\GithubImport;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @DI\Service()
 */
class ParseCsvGithubDocumentHandler extends AbstractDoctrineHandler
{
    /**
     * @var GithubImport
     */
    protected $importService;

    /**
     * BaseFrameworkHandler constructor.
     *
     * @DI\InjectParams({
     *     "validator" = @DI\Inject("validator"),
     *     "registry" = @DI\Inject("doctrine"),
     *     "importService" = @DI\Inject(GithubFilesBundle\Service\GithubImport::class)
     * })
     *
     * @param ValidatorInterface $validator
     * @param ManagerRegistry $registry
     * @param GithubImport $importService
     */
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry, GithubImport $importService)
    {
        parent::__construct($validator, $registry);
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
