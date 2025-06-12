<?php

declare(strict_types=1);

namespace App\Handler\Import;

use App\Command\Import\ImportAsnFromUrlCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Service\AsnImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportAsnFromUrlHandler extends AbstractDoctrineHandler
{
    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, protected AsnImport $importService)
    {
        parent::__construct($validator, $entityManager);
    }

    #[\Override]
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
