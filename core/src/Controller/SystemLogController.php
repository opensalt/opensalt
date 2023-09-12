<?php

namespace App\Controller;

use App\Repository\ChangeEntryRepository;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/system_log')]
#[IsGranted(Permission::MANAGE_SYSTEM_LOGS)]
class SystemLogController extends AbstractController
{
    public function __construct(private readonly ChangeEntryRepository $entryRepository)
    {
    }

    #[Route(path: '/', name: 'system_logs_show', methods: ['GET'])]
    public function showSystemLogs(): Response
    {
        return $this->render('system_log/show_system_logs.html.twig');
    }

    #[Route(path: '/revisions/{offset}/{limit}', name: 'system_logs_json', requirements: ['offset' => '\d+', 'limit' => '\d+'], defaults: ['offset' => 0, 'limit' => 0], methods: ['GET'])]
    public function listDocRevisions(int $offset = 0, int $limit = 0): Response
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

    #[Route(path: '/revisions/count', name: 'system_logs_count', methods: ['GET'])]
    public function changeLogCount(): Response
    {
        $count = $this->entryRepository->getChangeEntryCountForSystem();

        return new JsonResponse($count);
    }

    #[Route(path: '/export', name: 'system_logs_csv', methods: ['GET'])]
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
