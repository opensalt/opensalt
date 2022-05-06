<?php

namespace App\Controller;

use App\Entity\Framework\LsDoc;
use App\Repository\ChangeEntryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class DocRevisionController extends AbstractController
{
    private ChangeEntryRepository $entryRepository;

    public function __construct(ChangeEntryRepository $entryRepository)
    {
        $this->entryRepository = $entryRepository;
    }

    /**
     * @Security("is_granted('edit', doc)")
     */
    #[Route(path: '/cfdoc/{id}/revisions/{offset}/{limit}', methods: ['GET'], requirements: ['offset' => '\d+', 'limit' => '\d+'], defaults: ['offset' => 0, 'limit' => 0], name: 'doc_revisions_json')]
    public function listDocRevisionsAction(LsDoc $doc, int $offset, int $limit): Response
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-type', 'application/json');

        $response->setCallback(function () use ($doc, $limit, $offset) {
            $fd = fopen('php://output', 'wb+');
            fwrite($fd, '{"data": [');

            $history = $this->entryRepository->getChangeEntriesForDoc($doc, $limit, $offset);

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

    /**
     * @Security("is_granted('edit', doc)")
     */
    #[Route(path: '/cfdoc/{id}/revisions/export', name: 'doc_revisions_csv', methods: ['GET'])]
    public function exportDocRevisions(LsDoc $doc): Response
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="framework_log.csv"');

        $response->setCallback(function () use ($doc) {
            $fd = fopen('php://output', 'wb+');

            fputcsv($fd, ['Date/Time (UTC timezone)', 'Description', 'Username']);

            $history = $this->entryRepository->getChangeEntriesForDoc($doc, 0, 0);
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
