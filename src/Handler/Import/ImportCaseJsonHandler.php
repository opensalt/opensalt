<?php

namespace App\Handler\Import;

use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Command\Import\ImportCaseJsonCommand;
use App\Event\CommandEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CaseImport;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportCaseJsonHandler extends AbstractDoctrineHandler
{
    /**
     * @var CaseImport
     */
    protected $importService;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, CaseImport $caseImport)
    {
        parent::__construct($validator, $entityManager);
        $this->importService = $caseImport;
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        ini_set('memory_limit', '2G');
        set_time_limit(900);
        /** @var ImportCaseJsonCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $caseJson = $command->getCaseJson();
        $organization = $command->getOrganization();

        $doc = $this->importService->importCaseFile($caseJson);
        if ($organization) {
            $doc->setOrg($organization);
        }

        $notification = new NotificationEvent(
            'D12',
            sprintf('Framework "%s" imported from CASE JSON file', $doc->getTitle()),
            $doc,
            [
                'doc-a' => [
                    $doc,
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
