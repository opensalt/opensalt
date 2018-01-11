<?php

namespace App\Controller;

use App\Repository\ChangeEntryRepository;
use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use SimpleThings\EntityAudit\AuditReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
     * @Route("/cfdoc/{id}/revisions/{offset}/{limit}", defaults={"offset" = 0, "limit" = 0})
     * @Method("GET")
     * @Security("is_granted('edit', doc)")
     */
    public function listDocRevisionsAction(LsDoc $doc, int $offset, int $limit): Response
    {
        //$count = $this->entryRepository->getChangeEntryCountForDoc($doc);
        $history = $this->entryRepository->getChangeEntriesForDoc($doc, $limit, $offset);

        return new JsonResponse(['data' => $history]);
    }
}
