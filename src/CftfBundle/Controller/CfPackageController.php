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
        $repo = $this->getDoctrine()->getRepository(LsDoc::class);

        if ('json' === $_format) {
            $response = $this->generateBaseResponse($lsDoc->getUpdatedAt());

            if ($response->isNotModified($request)) {
                return $response;
            }

            $pkg = $repo->getPackageArray($lsDoc);

            $response->setContent($this->get('serializer')->serialize(
                $pkg,
                $request->getRequestFormat('json'),
                SerializationContext::create()->setGroups(['Default', 'CfPackage'])
            ));

            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename=opensalt-framework-'.$lsDoc->getIdentifier().'.json');

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
    protected function generateBaseResponse(\DateTimeInterface $lastModified): Response
    {
        $response = new Response();

        $response->setEtag(md5($lastModified->format('U')));
        $response->setLastModified($lastModified);
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->setExpires(\DateTime::createFromFormat('U', $lastModified->format('U'))->sub(new \DateInterval('PT1S')));
        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');

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
        $repo = $this->getDoctrine()->getRepository(LsDoc::class);

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
