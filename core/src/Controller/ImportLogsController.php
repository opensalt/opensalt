<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\MarkImportLogsAsReadCommand;
use App\Entity\Framework\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ImportLogsController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/cfdoc/{doc}/import_logs/mark_as_read", methods={"POST"}, name="mark_import_logs_as_read")
     * @Security("is_granted('edit', doc)")
     *
     * @return JsonResponse
     */
    public function markAsReadAction(LsDoc $doc): Response
    {
        $command = new MarkImportLogsAsReadCommand($doc);
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Logs marked as read successfully!'
        ]);
    }
}
