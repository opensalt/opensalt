<?php

namespace App\Handler\Import;

use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Command\Import\ImportCaseJsonCommand;
use App\Event\CommandEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CaseImport;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportCaseJsonHandler extends AbstractDoctrineHandler
{
    /**
     * @var CaseImport
     */
    protected $importService;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, CaseImport $caseImport, AuthorizationCheckerInterface $authChecker)
    {
        parent::__construct($validator, $entityManager);
        $this->importService = $caseImport;
        $this->authChecker = $authChecker;
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ImportCaseJsonCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $organization = $command->getOrganization();
        $user = $command->getUser();

        $doc = $this->importService->importCaseFile($command->getCaseJson());

        if (null !== $user && null !== $doc->getOrg() && !$this->authChecker->isGranted('edit', $doc)) {
            throw new \RuntimeException('The current user cannot update this framework');
        }

        if (null !== $organization && null === $doc->getOrg() && null === $doc->getUser()) {
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
