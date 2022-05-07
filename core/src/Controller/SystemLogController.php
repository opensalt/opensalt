<?php

namespace App\Controller;

use App\Repository\ChangeEntryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('manage', 'system_logs')")
 */
#[Route(path: '/admin/system_log')]
class SystemLogController extends AbstractController
{
    private ChangeEntryRepository $entryRepository;

    public function __construct(ChangeEntryRepository $entryRepository)
    {
        $this->entryRepository = $entryRepository;
    }

    #[Route(path: '/', methods: ['GET'], name: 'system_logs_show')]
    public function showSystemLogs(): Response
    {
        return $this->render('system_log/show_system_logs.html.twig');
    }

    #[Route(path: '/revisions/{offset}/{limit}', methods: ['GET'], requirements: ['offset' => '\d+', 'limit' => '\d+'], defaults: ['offset' => 0, 'limit' => 0], name: 'system_logs_json')]
    public function listDocRevisionsAction(int $offset = 0, int $limit = 0): Response
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-type', 'application/json');

        $response->setCallback(function () use ($limit, $offset) {
            $fd = fopen('php://output', 'wb+');
            fwrite($fd, '{"data": [');

            $history = $this->entryRepository->getChangeEntriesForSystem($limit, $offset);

            $first = true;
            foreach ($history as $line) {
                if (!$first) {
                    fwrite($fd, ',');
                } else {
                    $first = false;
                }
                fwrite($fd, json_encode($line, JSON_THROW_ON_ERROR));
            }

            fwrite($fd, ']}');
            fclose($fd);
        });

        return $response;
    }

    #[Route(path: '/revisions/count', methods: ['GET'], name: 'system_logs_count')]
    public function changeLogCount(): Response
    {
        $count = $this->entryRepository->getChangeEntryCountForSystem();

        return new JsonResponse($count);
    }

    #[Route(path: '/export', methods: ['GET'], name: 'system_logs_csv')]
    public function exportSystemLogs(): Response
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="system_log.csv"');

        $response->setCallback(function () {
            $fd = fopen('php://output', 'wb+');

            fputcsv($fd, ['Date/Time (UTC timezone)', 'Description', 'Username']);

            $history = $this->entryRepository->getChangeEntriesForSystem(0, 0);
            foreach ($history as $line) {
                fputcsv($fd, [
                    preg_replace('/\..*$/', '', $line['changed_at']),
                    $line['description'],
                    $line['username'],
                ]);
            }

            fclose($fd);
        });

        return $response;
    }
}
