<?php

namespace CftfBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Api1Controller
 *
 * @Route("/ims/case/v1p0")
 */
class Api1Controller extends Controller
{
    /**
     * @Route("/CFDocuments.{_format}", name="api_v1p1_cfdocuments", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getAllCfDocumentsAction(Request $request, $_format)
    {
        $limit = $request->query->get('limit', 100);
        $offset = $request->query->get('offset', 0);
        $sort = $request->query->get('sort', '');
        $orderBy = $request->query->get('orderBy', 'asc');
        $filter = $request->query->get('filter', '');
        $fields = $request->query->get('fields', []);

        /*
        $api = $this->get('case_api_v1p0');
        $docs = $api->getDocuments();
        */

        $repo = $this->getDoctrine()->getRepository('CftfBundle:LsDoc');
        $docs = $repo->findAllDocuments();

        $serializer = $this->get('serializer');
        $result = $serializer->serialize($docs, $_format);

        $response = new Response(
            $result,
            200,
            [
                'X-Total-Count' => count($docs),
            ]
        );

        return $response;
    }

    /**
     * @Route("/CFAssociationGroupings/{id}.{_format}", name="api_v1p1_cfassociationgrouping", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfAssociationGroupingAction(Request $request)
    {
    }

    /**
     * @Route("/CFConcepts/{id}.{_format}", name="api_v1p1_cfconcept", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfConceptAction(Request $request)
    {
    }

    /**
     * @Route("/CFDocuments/{id}.{_format}", name="api_v1p1_cfdocument", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfDocumentAction(Request $request)
    {
    }

    /**
     * @Route("/CFItems/{id}.{_format}", name="api_v1p1_cfitem", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfItemAction(Request $request)
    {
    }

    /**
     * @Route("/CFItemAssociations/{id}.{_format}", name="api_v1p1_cfitemassociations", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfItemAssociationAction(Request $request)
    {
    }

    /**
     * @Route("/CFItemTypes/{id}.{_format}", name="api_v1p1_cfitemtype", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfItemTypeAction(Request $request)
    {
    }

    /**
     * @Route("/CFLicenses/{id}.{_format}", name="api_v1p1_cflicense", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfLicenseAction(Request $request)
    {
    }

    /**
     * @Route("/CFPackages/{id}.{_format}", name="api_v1p1_cfpackage", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfPackageAction(Request $request)
    {
    }

    /**
     * @Route("/CFRubrics/{id}.{_format}", name="api_v1p1_cfrubric", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfRubricAction(Request $request)
    {
    }

    /**
     * @Route("/CFSubjects/{id}.{_format}", name="api_v1p1_cfsubject", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfSubjectAction(Request $request)
    {
    }
}
