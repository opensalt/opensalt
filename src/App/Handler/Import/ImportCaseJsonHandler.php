<?php

namespace App\Handler\Import;

use App\Handler\AbstractDoctrineHandler;
use App\Command\Import\ImportCaseJsonCommand;
use App\Event\CommandEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use App\Service\CaseImport;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @DI\Service()
 */
class ImportCaseJsonHandler extends AbstractDoctrineHandler
{
    /**
     * @var CaseImport
     */
    protected $importService;

    /**
     * constructor.
     *
     * @DI\InjectParams({
     *     "validator" = @DI\Inject("validator"),
     *     "registry" = @DI\Inject("doctrine"),
     *     "caseImport" = @DI\Inject(App\Service\CaseImport::class)
     * })
     *
     * @param ValidatorInterface $validator
     * @param ManagerRegistry $registry
     * @param CaseImport $caseImport
     */
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry, CaseImport $caseImport)
    {
        parent::__construct($validator, $registry);
        $this->importService = $caseImport;
    }
    /**
     * @DI\Observe(App\Command\Import\ImportCaseJsonCommand::class)
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ImportCaseJsonCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $caseJson = $command->getCaseJson();
        $organization = $command->getOrganization();

        $doc = $this->importService->importCaseFile($caseJson);
        if ($organization) {
            $doc->setOrg($organization);
        }
    }
}
