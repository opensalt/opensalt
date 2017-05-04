<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class CfPackageController
 *
 * @Route("/cfpackage")
 */
class CfPackageController extends Controller
{
    /**
     * Export a CFPackage
     *
     * @Route("/doc/{id}.{_format}", requirements={"_format"="(json|html|pdf|csv)"}, defaults={"_format"="json"}, name="cfpackage_export")
     * @Route("/doc/{id}/export.{_format}", requirements={"_format"="(json|html|pdf|csv)"}, defaults={"_format"="json"}, name="cfpackage_export2")
     * @Method("GET")
     */
    public function exportAction(LsDoc $lsDoc, $_format = 'json')
    {
    	$repo = $this->getDoctrine()->getRepository('CftfBundle:LsDoc');
        $items = $repo->findAllItems($lsDoc);
        $associations = $repo->findAllAssociations($lsDoc);
        // PW: this used to use findAllAssociationsForCapturedNodes, but that wouldn't export crosswalk associations

        $itemTypes = [];
        foreach ($items as $item) {
            if (!empty($item['itemType'])) {
                $itemTypes[$item['itemType']['code']] = $item['itemType'];
            }
        }

        $arr = [
            'lsDoc' => $lsDoc,
            'items' => $items,
            'associations' => $associations,
            'itemTypes' => $itemTypes,
            'subjects' => $lsDoc->getSubjects(),
            'concepts' => [],
            'licences' => [],
            'associationGroupings' => [],
        ];
        $response = new Response($this->renderView("CftfBundle:CfPackage:export.$_format.twig", $arr));

        if ($_format == 'json') {
            $response->headers->set('Content-Type', 'text/json');
            $response->headers->set('Content-Disposition', 'attachment; filename=opensalt-framework-'.$lsDoc->getIdentifier().'.json');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
