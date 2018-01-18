<?php

namespace App\Handler\Import;

use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Command\Import\ImportAsnFromUrlCommand;
use App\Event\CommandEvent;
use App\Service\AsnImport;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @DI\Service()
 */
class ImportAsnFromUrlHandler extends AbstractDoctrineHandler
{
    /**
     * @var AsnImport
     */
    protected $importService;

    /**
     * BaseFrameworkHandler constructor.
     *
     * @DI\InjectParams({
     *     "validator" = @DI\Inject("validator"),
     *     "registry" = @DI\Inject("doctrine"),
     *     "asnImportService" = @DI\Inject(App\Service\AsnImport::class)
     * })
     *
     * @param ValidatorInterface $validator
     * @param ManagerRegistry $registry
     * @param AsnImport $asnImportService
     */
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry, AsnImport $asnImportService)
    {
        parent::__construct($validator, $registry);
        $this->importService = $asnImportService;
    }

    /**
     * @DI\Observe(App\Command\Import\ImportAsnFromUrlCommand::class)
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ImportAsnFromUrlCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $asnId = $command->getAsnIdOrUrl();
        $creator = $command->getCreator();
        $organization = $command->getOrganization();

        $doc = $this->importService->generateFrameworkFromAsn($asnId, $creator);
        if ($organization) {
            $doc->setOrg($organization);
        }

        $notification = new NotificationEvent(
            'D11',
            sprintf('Framework "%s" imported from ASN', $doc->getTitle()),
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
