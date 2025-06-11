<?php

declare(strict_types=1);

namespace App\Controller\Cms;

use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/cms')]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly LsDocRepository $lsDocRepository,
    ) {
    }

    /**
     * Generate JSON formatted for export to CMS.
     */
    #[Route(path: '/cfdoc/{id}.{_format}', name: 'lsdoc_api_view', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function export(LsDoc $lsDoc, string $_format = 'json'): Response
    {
        $items = $this->lsDocRepository->findAllChildrenArray($lsDoc);

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
