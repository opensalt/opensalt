<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\MarkImportLogsAsReadCommand;
use App\Entity\Framework\LsDoc;
use App\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ImportLogsController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/cfdoc/{doc}/import_logs/mark_as_read', name: 'mark_import_logs_as_read', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    public function markAsRead(LsDoc $doc): JsonResponse
    {
        $command = new MarkImportLogsAsReadCommand($doc);
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Logs marked as read successfully!',
        ]);
    }
}
