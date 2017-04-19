<?php

namespace CftfBundle\Controller;

use CftfBundle\Api\v1p0\DTO\ImsxCodeMinor;
use CftfBundle\Api\v1p0\DTO\ImsxCodeMinorField;
use CftfBundle\Api\v1p0\DTO\ImsxStatusInfo;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Entity\LsDefConcept;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDefLicence;
use CftfBundle\Entity\LsDefSubject;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\Query;
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
        /*
        $limit = $request->query->get('limit', 100);
        $offset = $request->query->get('offset', 0);
        $sort = $request->query->get('sort', '');
        $orderBy = $request->query->get('orderBy', 'asc');
        $filter = $request->query->get('filter', '');
        $fields = $request->query->get('fields', []);
        */

        $repo = $this->getDoctrine()->getRepository(LsDoc::class);
        $results = $repo->findAllDocuments();

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

        $response = $this->generateBaseReponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent($this->get('serializer')->serialize(['CFDocuments' => $docs], $_format));
        $response->headers->set('X-Total-Count', count($docs));

        return $response;
    }

    /**
     * @Route("/CFDocuments/{id}.{_format}", name="api_v1p1_cfdocument", defaults={"_format"="json"})
     * @Method("GET")
     *
     * @param Request $request
     * @param string $id
     * @param string $_format
     *
     * @return Response
     */
    public function getCfDocumentAction(Request $request, $id, $_format)
    {
        return $this->generateObjectResponse(LsDoc::class, $request, $id, $_format);
    }

    /**
     * @Route("/CFAssociationGroupings/{id}.{_format}", name="api_v1p1_cfassociationgrouping", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfAssociationGroupingAction(Request $request, $id, $_format)
    {
        return $this->generateObjectResponse(LsDefAssociationGrouping::class, $request, $id, $_format);
    }

    /**
     * @Route("/CFConcepts/{id}.{_format}", name="api_v1p1_cfconcept", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfConceptAction(Request $request, $id, $_format)
    {
        return $this->generateObjectResponse(LsDefConcept::class, $request, $id, $_format);
    }

    /**
     * @Route("/CFItems/{id}.{_format}", name="api_v1p1_cfitem", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfItemAction(Request $request, $id, $_format)
    {
        return $this->generateObjectResponse(LsItem::class, $request, $id, $_format);
    }

    /**
     * @Route("/CFItemAssociations/{id}.{_format}", name="api_v1p1_cfitemassociations", defaults={"_format"="json"})
     * @Route("/CFItems/{id}/associations.{_format}", name="api_v1p1_cfitemassociations2", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfItemAssociationsAction(Request $request, $id, $_format)
    {
        $repo = $this->getDoctrine()->getRepository(LsAssociation::class);
        $results = $repo->findAllAssociationsFor($id);

        if (null === $results) {
            return $this->generate404($id, $_format);
        }

        $associations = [];
        $lastModified = new \DateTime('now - 10 years');
        foreach ($results as $association) {
            /* @var LsAssociation $association */
            $associations[] = $association;
            if ($association->getUpdatedAt() > $lastModified) {
                $lastModified = $association->getUpdatedAt();
            }
        }

        $response = $this->generateBaseReponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent($this->get('serializer')->serialize(['CFAssociations' => $associations], $_format));
        $response->headers->set('X-Total-Count', count($associations));

        return $response;
    }

    /**
     * @Route("/CFAssociations/{id}.{_format}", name="api_v1p1_cfassociation", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfAssociationAction(Request $request, $id, $_format)
    {
        return $this->generateObjectResponse(LsAssociation::class, $request, $id, $_format);
    }


    /**
     * @Route("/CFItemTypes/{id}.{_format}", name="api_v1p1_cfitemtype", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfItemTypeAction(Request $request, $id, $_format)
    {
        return $this->generateObjectResponse(LsDefItemType::class, $request, $id, $_format);
    }

    /**
     * @Route("/CFLicenses/{id}.{_format}", name="api_v1p1_cflicense", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfLicenseAction(Request $request, $id, $_format)
    {
        return $this->generateObjectResponse(LsDefLicence::class, $request, $id, $_format);
    }

    /**
     * @Route("/CFPackages/{id}.{_format}", name="api_v1p1_cfpackage", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfPackageAction(Request $request, $id, $_format)
    {
        $repo = $this->getDoctrine()->getRepository(LsDoc::class);
        /* @var LsDoc $doc */
        $doc = $repo->findOneBy(['identifier' => $id]);

        if (null === $doc) {
            return $this->generate404($id, $_format);
        }

        $response = $this->generateBaseReponse($doc->getUpdatedAt());
        if ($response->isNotModified($request)) {
            return $response;
        }

        $pkg = [
            'CFDocument' => $doc,
            'CFItems' => array_values($repo->findAllItems($doc, Query::HYDRATE_OBJECT)),
            'CFAssociations' => array_values($repo->findAllAssociations($doc, Query::HYDRATE_OBJECT)),
            'CFDefinitions' => [
                'CFConcepts' => [],
                'CFSubjects' => $doc->getSubjects(),
                'CFLicenses' => [],
                'CFItemTypes' => $repo->findAllUsedItemTypes($doc, Query::HYDRATE_OBJECT),
                'CFAssociationGroupings' => $repo->findAllUsedAssociationGroups($doc, Query::HYDRATE_OBJECT),
            ],
            'CFRubrics' => [],
        ];

        $response->setContent($this->get('serializer')->serialize($pkg, $_format));

        return $response;
    }

    /**
     * @Route("/CFRubrics/{id}.{_format}", name="api_v1p1_cfrubric", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfRubricAction(Request $request, $id, $_format)
    {
        return $this->generate404($id, $_format);
    }

    /**
     * @Route("/CFSubjects/{id}.{_format}", name="api_v1p1_cfsubject", defaults={"_format"="json"})
     * @Method("GET")
     */
    public function getCfSubjectAction(Request $request, $id, $_format)
    {
        return $this->generateObjectResponse(LsDefSubject::class, $request, $id, $_format);
    }

    /**
     * Generate a base response
     *
     * @param Response $response
     * @param \DateTime $lastModified
     *
     * @return Response
     */
    protected function generateBaseReponse(\DateTime $lastModified)
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
     * @param string $identifier
     * @param string $_format
     *
     * @return Response
     */
    protected function generate404(string $identifier, string $_format)
    {
        // Object not found
        $errField = new ImsxCodeMinorField('id', ImsxCodeMinorField::CODE_MINOR_UNKNOWN_OBJECT);
        $errMinor = new ImsxCodeMinor([$errField]);
        $err = new ImsxStatusInfo(
            ImsxStatusInfo::CODE_MAJOR_FAILURE,
            ImsxStatusInfo::SEVERITY_ERROR,
            $errMinor
        );

        $serializer = $this->get('serializer');
        $response = new Response(
            $serializer->serialize($err, $_format),
            404
        );

        $response->setMaxAge(60);
        $response->setSharedMaxAge(60);
        $response->setPublic();

        return $response;
    }

    protected function generateObjectResponse(string $type, Request $request, $id, $_format)
    {
        $repo = $this->getDoctrine()->getRepository($type);
        $doc = $repo->findOneBy(['identifier' => $id]);

        if (empty($doc)) {
            return $this->generate404($id, $_format);
        }

        $response = new Response();
        $response->setEtag(md5($doc->getUpdatedAt()->format('U')));
        $response->setLastModified($doc->getUpdatedAt());
        $response->setMaxAge(60);
        $response->setSharedMaxAge(60);
        $response->setPublic();

        if ($response->isNotModified($request)) {
            return $response;
        }

        $serializer = $this->get('serializer');
        $result = $serializer->serialize($doc, $_format);

        $response->setContent($result);

        return $response;
    }
}
