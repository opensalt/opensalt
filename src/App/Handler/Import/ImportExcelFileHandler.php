<?php

namespace App\Handler\Import;

use App\Handler\AbstractDoctrineHandler;
use App\Command\Import\ImportExcelFileCommand;
use App\Event\CommandEvent;
use App\Service\ExcelImport;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @DI\Service()
 */
class ImportExcelFileHandler extends AbstractDoctrineHandler
{
    /**
     * @var ExcelImport
     */
    protected $importService;

    /**
     * BaseFrameworkHandler constructor.
     *
     * @DI\InjectParams({
     *     "validator" = @DI\Inject("validator"),
     *     "registry" = @DI\Inject("doctrine"),
     *     "excelImportService" = @DI\Inject(App\Service\ExcelImport::class)
     * })
     *
     * @param ValidatorInterface $validator
     * @param ManagerRegistry $registry
     * @param ExcelImport $excelImportService
     */
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry, ExcelImport $excelImportService)
    {
        parent::__construct($validator, $registry);
        $this->importService = $excelImportService;
    }
    /**
     * @DI\Observe(App\Command\Import\ImportExcelFileCommand::class)
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ImportExcelFileCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $excelFilePath = $command->getExcelFilePath();
        $creator = $command->getCreator();
        $organization = $command->getOrganization();

        $lsDoc = $this->importService->importExcel($excelFilePath);
        if ($creator) {
            $lsDoc->setCreator($creator);
        }
        if ($organization) {
            $lsDoc->setOrg($organization);
        }
    }
}
