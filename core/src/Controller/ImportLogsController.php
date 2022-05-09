<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\MarkImportLogsAsReadCommand;
use App\Entity\Framework\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ImportLogsController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/cfdoc/{doc}/import_logs/mark_as_read', name: 'mark_import_logs_as_read', methods: ['POST'])]
    #[Security("is_granted('edit', doc)")]
    public function markAsReadAction(LsDoc $doc): JsonResponse
    {
        $command = new MarkImportLogsAsReadCommand($doc);
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Logs marked as read successfully!',
        ]);
    }
}
