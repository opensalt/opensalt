<?php

namespace App\Handler\Import;

use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Command\Import\MarkImportLogsAsReadCommand;
use App\Event\CommandEvent;
use App\Service\AsnImport;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @DI\Service()
 */
class MarkImportLogsAsReadHandler extends AbstractDoctrineHandler
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
     *     "registry" = @DI\Inject("doctrine")
     * })
     *
     * @param ValidatorInterface $validator
     * @param ManagerRegistry $registry
     */
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry)
    {
        parent::__construct($validator, $registry);
    }

    /**
     * @DI\Observe(App\Command\Import\MarkImportLogsAsReadCommand::class)
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var MarkImportLogsAsReadCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $doc = $command->getDoc();
        foreach ($doc->getImportLogs() as $log) {
            $log->markAsRead();
        }

        $notification = new NotificationEvent(
            'D14',
            sprintf('Import logs of "%s" marked as read', $doc->getTitle()),
            $doc,
            [
            ],
            false
        );
        $command->setNotificationEvent($notification);
    }
}
