<?php

namespace App\Controller\Cms;

use App\Entity\Framework\LsDoc;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/cms")
 */
class ExportController extends AbstractController
{
    /**
     * Generate JSON formatted for export to CMS
     *
     * @Route("/cfdoc/{id}.{_format}", methods={"GET"}, name="lsdoc_api_view", requirements={"id"="\d+"})
     * @Template()
     *
     * @param \App\Entity\Framework\LsDoc $lsDoc
     * @param string $_format
     *
     * @return array
     */
    public function exportAction(LsDoc $lsDoc, $_format = 'json'): array
    {
        $items = $this->getDoctrine()->getRepository(LsDoc::class)->findAllChildrenArray($lsDoc);

        return [
            'lsDoc' => $lsDoc,
            'items' => $items,
        ];
    }

}
