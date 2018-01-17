<?php

namespace App\Controller;

use App\Repository\ChangeEntryRepository;
use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use SimpleThings\EntityAudit\AuditReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocRevisionController extends AbstractController
{
    /**
     * @var AuditReader
     */
    protected $auditReader;

    /**
     * @var ChangeEntryRepository
     */
    private $entryRepository;

    public function __construct(AuditReader $auditReader, ChangeEntryRepository $entryRepository)
    {
        $this->auditReader = $auditReader;
        $this->entryRepository = $entryRepository;
    }

    /**
     * @Route("/cfdoc/{id}/revisions/{offset}/{limit}", defaults={"offset" = 0, "limit" = 0}, name="doc_revisions_json")
     * @Method("GET")
     * @Security("is_granted('edit', doc)")
     */
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
                fwrite($fd, json_encode($line));
            }

            fwrite($fd, ']}');
            fclose($fd);
        });

        return $response;
    }
}
