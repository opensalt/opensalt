<?php

namespace App\Handler\Import;

use App\Command\Import\ImportExcelFileCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Service\ExcelImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportExcelFileHandler extends AbstractDoctrineHandler
{
    /**
     * @var ExcelImport
     */
    protected $importService;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, ExcelImport $excelImportService)
    {
        parent::__construct($validator, $entityManager);
        $this->importService = $excelImportService;
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ImportExcelFileCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $excelFilePath = $command->getExcelFilePath();
        $creator = $command->getCreator();
        $organization = $command->getOrganization();

        $doc = $this->importService->importExcel($excelFilePath);
        if ($creator) {
            $doc->setCreator($creator);
        }
        if ($organization) {
            $doc->setOrg($organization);
        }

        $notification = new NotificationEvent(
            'D13',
            sprintf('Framework "%s" imported from Excel file', $doc->getTitle()),
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
