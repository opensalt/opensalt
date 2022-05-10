<?php

namespace App\Handler\Import;

use App\Command\Import\ImportCaseJsonCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use App\Security\Permission;
use App\Service\CaseImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportCaseJsonHandler extends AbstractDoctrineHandler
{
    public function __construct(
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        protected CaseImport $caseImport,
        private readonly AuthorizationCheckerInterface $authChecker,
    ) {
        parent::__construct($validator, $entityManager);
    }

    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var ImportCaseJsonCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $organization = $command->getOrganization();
        $user = $command->getUser();

        $doc = $this->caseImport->importCaseFile($command->getCaseJson());

        if (null !== $user && null !== $doc->getOrg() && !$this->authChecker->isGranted(Permission::FRAMEWORK_EDIT, $doc)) {
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
