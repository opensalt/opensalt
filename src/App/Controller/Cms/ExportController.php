<?php

namespace App\Controller\Cms;

use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

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
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     * @param string $_format
     *
     * @return Response
     */
    public function exportAction(LsDoc $lsDoc, $_format = 'json')
    {
        $items = $this->getDoctrine()->getRepository(LsDoc::class)->findAllChildrenArray($lsDoc);

        return $this->render('cms/export.'.$_format.'.twig', [
            'lsDoc' => $lsDoc,
            'items' => $items,
        ]);
    }

}
