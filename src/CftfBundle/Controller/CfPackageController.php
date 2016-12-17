<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
     * @Route("/lsdoc/{id}.{_format}", requirements={"_format"="(json|html)"}, defaults={"_format"="json"}, name="cfpackage_export")
     * @Route("/lsdoc/{id}/export.{_format}", requirements={"_format"="(json|html)"}, defaults={"_format"="json"}, name="cfpackage_export2")
     * @Method("GET")
     * @Template()
     */
    public function exportAction(LsDoc $lsDoc, $_format = 'json')
    {
        $items = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findAllItems($lsDoc);
        $associations = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findAllAssociationsForCapturedNodes($lsDoc);

        $itemTypes = [];
        foreach ($items as $item) {
            if (!empty($item['itemType'])) {
                $itemTypes[$item['itemType']['code']] = $item['itemType'];
            }
        }

        return [
            'lsDoc' => $lsDoc,
            'items' => $items,
            'associations' => $associations,
            'itemTypes' => $itemTypes,
            'subjects' => $lsDoc->getSubjects(),
            'concepts' => [],
            'licences' => [],
            'associationGroupings' => [],
        ];
    }
}