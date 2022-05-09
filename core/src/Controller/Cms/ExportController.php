<?php

namespace App\Controller\Cms;

use App\Entity\Framework\LsDoc;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/cms')]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Generate JSON formatted for export to CMS.
     */
    #[Route(path: '/cfdoc/{id}.{_format}', name: 'lsdoc_api_view', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function exportAction(LsDoc $lsDoc, string $_format = 'json'): Response
    {
        $items = $this->managerRegistry->getRepository(LsDoc::class)->findAllChildrenArray($lsDoc);

        $params = [
            'lsDoc' => $lsDoc,
            'items' => $items,
        ];

        if ('html' === $_format) {
            return $this->render('cms/export/export.html.twig', $params);
        }

        return $this->render('cms/export/export.json.twig', $params);
    }
}
