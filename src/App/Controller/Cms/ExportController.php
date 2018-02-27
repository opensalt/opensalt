<?php

namespace App\Controller\Cms;

use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
     * @Route("/cfdoc/{id}.{_format}", name="lsdoc_api_view", requirements={"id"="\d+"})
     * @Method({"GET"})
     * @Template()
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
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
