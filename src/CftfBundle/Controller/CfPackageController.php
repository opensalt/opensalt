<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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
    public function exportAction(Request $request, LsDoc $lsDoc, $_format = 'json')
    {
        $repo = $this->getDoctrine()->getRepository('CftfBundle:LsDoc');

        if ('json' === $_format) {
            $pkg = $repo->getPackageArray($lsDoc);

            $response = $this->generateBaseReponse($lsDoc->getUpdatedAt());

            if ($response->isNotModified($request)) {
                return $response;
            }

            $response->setContent($this->get('serializer')->serialize(
                $pkg,
                $request->getRequestFormat('json'),
                SerializationContext::create()->setGroups(['Default', 'CfPackage'])
            ));

            $response->headers->set('Content-Type', 'text/json');
            $response->headers->set('Content-Disposition', 'attachment; filename=opensalt-framework-'.$lsDoc->getIdentifier().'.json');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        }

        $arr = $this->generateSimplePackageArray($lsDoc);

        return new Response($this->renderView("CftfBundle:CfPackage:export.$_format.twig", $arr));
    }

    /**
     * Generate a base response
     *
     * @param \DateTimeInterface $lastModified
     *
     * @return Response
     */
    protected function generateBaseReponse(\DateTimeInterface $lastModified): Response
    {
        $response = new Response();

        $response->setEtag(md5($lastModified->format('U')));
        $response->setLastModified($lastModified);
        $response->setMaxAge(60);
        $response->setSharedMaxAge(60);
        $response->setPublic();

        return $response;
    }

    /**
     * Generate an array representing the package
     *
     * Note that this array does not match the API output
     *
     * @param LsDoc $doc
     *
     * @return array
     */
    protected function generateSimplePackageArray(LsDoc $doc): array
    {
        $repo = $this->getDoctrine()->getRepository('CftfBundle:LsDoc');

        $items = $repo->findAllItems($doc);
        $associations = $repo->findAllAssociations($doc);
        // PW: this used to use findAllAssociationsForCapturedNodes, but that wouldn't export crosswalk associations

        $itemTypes = [];
        foreach ($items as $item) {
            if (!empty($item['itemType'])) {
                $itemTypes[$item['itemType']['code']] = $item['itemType'];
            }
        }

        $arr = [
            'lsDoc' => $doc,
            'items' => $items,
            'associations' => $associations,
            'itemTypes' => $itemTypes,
            'subjects' => $doc->getSubjects(),
            'concepts' => [],
            'licences' => [],
            'associationGroupings' => [],
        ];

        return $arr;
    }
}
