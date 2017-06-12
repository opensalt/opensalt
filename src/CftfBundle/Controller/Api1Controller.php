<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\CaseApiInterface;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Repository\CfDocQuery;
use Doctrine\ORM\Query;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
     * @Route("/CFDocuments.{_format}", name="api_v1p0_cfdocuments", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getAllCfDocumentsAction(Request $request): Response
    {
        $limit = $request->query->get('limit', 100);
        $offset = $request->query->get('offset', 0);
        /*
        $sort = $request->query->get('sort', '');
        $orderBy = $request->query->get('orderBy', 'asc');
        $filter = $request->query->get('filter', '');
        $fields = $request->query->get('fields', []);
        */

        $query = new CfDocQuery();
        $query->limit = $limit;
        $query->offset = $offset;

        $repo = $this->getDoctrine()->getRepository(LsDoc::class);
        $results = $repo->findAllDocuments($query);

        $docs = [];
        $lastModified = new \DateTime('now - 10 years');
        foreach ($results as $doc) {
            /** @var LsDoc $doc */
            if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT !== $doc->getAdoptionStatus()) {
                $docs[] = $doc;
                if ($doc->getUpdatedAt() > $lastModified) {
                    $lastModified = $doc->getUpdatedAt();
                }
            }
        }

        $this->get('logger')->info('CASE API: getAllCfDocuments', []);

        $response = $this->generateBaseReponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent($this->get('serializer')->serialize(
            ['CFDocuments' => $docs],
            $request->getRequestFormat('json'),
            SerializationContext::create()->setGroups(['Default', 'CfDocuments'])
        ));
        $response->headers->set('X-Total-Count', count($docs));

        return $response;
    }

    /**
     * @Route("/CFPackages/{id}.{_format}", name="api_v1p0_cfpackage", defaults={"_format"="json"})
     * @Method("GET")
     * @ParamConverter("obj", class="CftfBundle:LsDoc", options={"repository_method"="findOneByIdentifier"})
     */
    public function getCfPackageAction(Request $request, LsDoc $obj): Response
    {
        $repo = $this->getDoctrine()->getRepository(LsDoc::class);
        $doc = $obj;
        $id = $obj->getIdentifier();

        $this->get('logger')->info('CASE API: package returned', ['id' => $id]);

        $response = $this->generateBaseReponse($doc->getUpdatedAt());
        if ($response->isNotModified($request)) {
            return $response;
        }

        $pkg = [
            'CFDocument' => $doc,
            'CFItems' => array_values($repo->findAllItems($doc, Query::HYDRATE_OBJECT)),
            'CFAssociations' => array_values($repo->findAllAssociations($doc, Query::HYDRATE_OBJECT)),
            'CFDefinitions' => [
                'CFConcepts' => $repo->findAllUsedConcepts($doc, Query::HYDRATE_OBJECT),
                'CFSubjects' => $doc->getSubjects(),
                'CFLicenses' => $repo->findAllUsedLicences($doc, Query::HYDRATE_OBJECT),
                'CFItemTypes' => $repo->findAllUsedItemTypes($doc, Query::HYDRATE_OBJECT),
                'CFAssociationGroupings' => $repo->findAllUsedAssociationGroups($doc, Query::HYDRATE_OBJECT),
            ]
        ];

        $rubrics = $repo->findAllUsedRubrics($doc, Query::HYDRATE_OBJECT);
        if (0 < count($rubrics)) {
            $pkg['CFRubrics'] = $rubrics;
        }

        $response->setContent($this->get('serializer')->serialize(
            $pkg,
            $request->getRequestFormat('json'),
            SerializationContext::create()->setGroups(['Default', 'CfPackage'])
        ));

        return $response;
    }

    /**
     * @Route("/CFItemAssociations/{id}.{_format}", name="api_v1p0_cfitemassociations", defaults={"_format"="json"})
     * @Route("/CFItems/{id}/associations.{_format}", name="api_v1p0_cfitemassociations2", defaults={"_format"="json"})
     * @Method("GET")
     * @ParamConverter("obj", class="CftfBundle:LsItem", options={"repository_method"="findOneByIdentifier"})
     */
    public function getCfItemAssociationsAction(Request $request, LsItem $obj): Response
    {
        $item = $obj;
        $id = $obj->getIdentifier();

        $results = $this->getDoctrine()
            ->getRepository(LsAssociation::class)
            ->findAllAssociationsFor($id);

        $associations = [];
        $lastModified = $item->getUpdatedAt();
        foreach ($results as $association) {
            /* @var LsAssociation $association */
            $associations[] = $association;
            if ($association->getUpdatedAt() > $lastModified) {
                $lastModified = $association->getUpdatedAt();
            }
        }

        $this->get('logger')->info('CASE API: item associations returned', ['id' => $id]);

        $response = $this->generateBaseReponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent($this->get('serializer')->serialize(
            [
                'CFItem' => $item,
                'CFAssociations' => $associations,
            ],
            $request->getRequestFormat('json'),
            SerializationContext::create()->setGroups(['Default', 'CfItemAssociations'])
        ));
        $response->headers->set('X-Total-Count', count($associations));

        return $response;
    }

    /**
     * @Route("/CFAssociationGroupings/{id}.{_format}", name="api_v1p0_cfassociationgrouping", defaults={"class"="CftfBundle:LsDefAssociationGrouping", "_format"="json"})
     * @Route("/CFAssociations/{id}.{_format}", name="api_v1p0_cfassociation", defaults={"class"="CftfBundle:LsAssociation", "_format"="json"})
     * @Route("/CFDocuments/{id}.{_format}", name="api_v1p0_cfdocument", defaults={"class"="CftfBundle:LsDoc", "_format"="json"})
     * @Route("/CFItems/{id}.{_format}", name="api_v1p0_cfitem", defaults={"class"="CftfBundle:LsItem", "_format"="json"})
     * @Route("/CFLicenses/{id}.{_format}", name="api_v1p0_cflicense", defaults={"class"="CftfBundle:LsDefLicence", "_format"="json"})
     * @Route("/CFRubrics/{id}.{_format}", name="api_v1p0_cfrubric", defaults={"class"="CftfBundle:CfRubric", "_format"="json"})
     * @Route("/CFRubricCriteria/{id}.{_format}", name="api_v1p0_cfrubriccriterion", defaults={"class"="CftfBundle:CfRubricCriterion", "_format"="json"})
     * @Route("/CFRubricCriterionLevels/{id}.{_format}", name="api_v1p0_cfrubriccriterionlevel", defaults={"class"="CftfBundle:CfRubricCriterionLevel", "_format"="json"})
     * @Method("GET")
     * @ParamConverter("obj", class="CftfBundle:LsDoc", options={"id"={"id", "class"}, "repository_method"="apiFindOneByClassIdentifier"})
     *
     * @param Request $request
     * @param CaseApiInterface $obj
     * @param string $_format
     *
     * @return Response
     */
    public function getObjectAction(Request $request, CaseApiInterface $obj): Response
    {
        return $this->generateObjectResponse($request, $obj);
    }

    /**
     * @Route("/CFConcepts/{id}.{_format}", name="api_v1p0_cfconcept", defaults={"class"="CftfBundle:LsDefConcept", "_format"="json"})
     * @Route("/CFItemTypes/{id}.{_format}", name="api_v1p0_cfitemtype", defaults={"class"="CftfBundle:LsDefItemType", "_format"="json"})
     * @Route("/CFSubjects/{id}.{_format}", name="api_v1p0_cfsubject", defaults={"class"="CftfBundle:LsDefSubject", "_format"="json"})
     * @Method("GET")
     * @ParamConverter("obj", class="CftfBundle:LsDoc", options={"id"={"id", "class"}, "repository_method"="apiFindOneByClassIdentifier"})
     *
     * @param Request $request
     * @param CaseApiInterface $obj
     * @param string $_format
     *
     * @return Response
     */
    public function getObjectCollectionAction(Request $request, CaseApiInterface $obj): Response
    {
        return $this->generateObjectCollectionResponse($request, $obj);
    }

    /**
     * Generate a base response
     *
     * @param Response $response
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
   * Generate a response for a single object
   *
   * @param Request $request
   * @param CaseApiInterface $obj
   *
   * @return Response
   */
    protected function generateObjectResponse(Request $request, CaseApiInterface $obj): Response
    {
        $this->get('logger')->info('CASE API: Returned object', ['type' => get_class($obj), 'id' => $obj->getIdentifier()]);

        $response = $this->generateBaseReponse($obj->getUpdatedAt());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $serializer = $this->get('serializer');
        $result = $serializer->serialize(
            $obj,
            $request->getRequestFormat('json'),
            SerializationContext::create()->setGroups([
                'Default',
                preg_replace('/.*\\\\/', '', get_class($obj)),
            ])
        );

        $response->setContent($result);

        return $response;
    }

    /**
     * Generate a response for a collection of objects
     *
     * @param Request $request
     * @param CaseApiInterface $obj
     *
     * @return Response
     */
    protected function generateObjectCollectionResponse(Request $request, CaseApiInterface $obj): Response
    {
        $this->get('logger')->info('CASE API: Returned object', ['type' => get_class($obj), 'id' => $obj->getIdentifier()]);

        $response = $this->generateBaseReponse(new \DateTime());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $collection = explode('/', $request->getPathInfo())[4];

        $serializer = $this->get('serializer');
        $result = $serializer->serialize(
            [$collection => [$obj]],
            $request->getRequestFormat('json'),
            SerializationContext::create()->setGroups([
                'Default',
                preg_replace('/.*\\\\/', '', get_class($obj)),
            ])
        );

        $response->setContent($result);

        return $response;
    }
}
