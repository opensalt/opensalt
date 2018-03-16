<?php

namespace App\Controller\Api;

use App\Entity\ChangeEntry;
use App\Entity\Framework\CaseApiInterface;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\CfDocQuery;
use App\Repository\Framework\LsDocRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Api1Controller
 *
 * @Route("/ims/case/v1p0")
 */
class Api1Controller extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    public function __construct(LoggerInterface $logger, SerializerInterface $serializer)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

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

        $this->logger->info('CASE API: getAllCfDocuments', []);

        $response = $this->generateBaseReponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent($this->serializer->serialize(
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
     * @Entity("obj", expr="repository.findOneByIdentifier(id)")
     */
    public function getCfPackageAction(Request $request, LsDoc $obj): Response
    {
        $repo = $this->getDoctrine()->getRepository(LsDoc::class);
        $doc = $obj;
        $id = $obj->getIdentifier();

        $this->logger->info('CASE API: package returned', ['id' => $id]);

        $changeRepo = $this->getDoctrine()->getRepository(ChangeEntry::class);
        $lastChange = $changeRepo->getLastChangeTimeForDoc($doc);

        $lastModified = $doc->getUpdatedAt();
        if (false !== $lastChange && null !== $lastChange['changed_at']) {
            $lastModified = new \DateTime($lastChange['changed_at'], new \DateTimeZone('UTC'));
        }
        $response = $this->generateBaseReponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $pkg = $repo->getPackageArray($doc);

        $response->setContent($this->serializer->serialize(
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
     * @Entity("obj", expr="repository.findOneByIdentifier(id)")
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

        $this->logger->info('CASE API: item associations returned', ['id' => $id]);

        $response = $this->generateBaseReponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent($this->serializer->serialize(
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
     * @Route("/CFAssociationGroupings/{id}.{_format}", name="api_v1p0_cfassociationgrouping", defaults={"class"="App\Entity\Framework\LsDefAssociationGrouping", "_format"="json"})
     * @Route("/CFAssociations/{id}.{_format}", name="api_v1p0_cfassociation", defaults={"class"="App\Entity\Framework\LsAssociation", "_format"="json"})
     * @Route("/CFDocuments/{id}.{_format}", name="api_v1p0_cfdocument", defaults={"class"="App\Entity\Framework\LsDoc", "_format"="json"})
     * @Route("/CFItems/{id}.{_format}", name="api_v1p0_cfitem", defaults={"class"="App\Entity\Framework\LsItem", "_format"="json"})
     * @Route("/CFLicenses/{id}.{_format}", name="api_v1p0_cflicense", defaults={"class"="App\Entity\Framework\LsDefLicence", "_format"="json"})
     * @Route("/CFRubrics/{id}.{_format}", name="api_v1p0_cfrubric", defaults={"class"="App\Entity\Framework\CfRubric", "_format"="json"})
     * @Route("/CFRubricCriteria/{id}.{_format}", name="api_v1p0_cfrubriccriterion", defaults={"class"="App\Entity\Framework\CfRubricCriterion", "_format"="json"})
     * @Route("/CFRubricCriterionLevels/{id}.{_format}", name="api_v1p0_cfrubriccriterionlevel", defaults={"class"="App\Entity\Framework\CfRubricCriterionLevel", "_format"="json"})
     * @Method("GET")
     */
    public function getObjectAction(Request $request, LsDocRepository $repo, $class, $id): Response
    {
        $obj = $repo->apiFindOneByClassIdentifier(['class' => $class, 'id' => $id]);

        return $this->generateObjectResponse($request, $obj);
    }

    /**
     * @Route("/CFConcepts/{id}.{_format}", name="api_v1p0_cfconcept", defaults={"class"="App\Entity\Framework\LsDefConcept", "_format"="json"})
     * @Route("/CFItemTypes/{id}.{_format}", name="api_v1p0_cfitemtype", defaults={"class"="App\Entity\Framework\LsDefItemType", "_format"="json"})
     * @Route("/CFSubjects/{id}.{_format}", name="api_v1p0_cfsubject", defaults={"class"="App\Entity\Framework\LsDefSubject", "_format"="json"})
     * @Method("GET")
     */
    public function getObjectCollectionAction(Request $request, LsDocRepository $repo, $class, $id): Response
    {
        $obj = $repo->apiFindOneByClassIdentifier(['class' => $class, 'id' => $id]);

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

        $response->setEtag(md5($lastModified->format('U.u')), true);
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
        $this->logger->info('CASE API: Returned object', ['type' => get_class($obj), 'id' => $obj->getIdentifier()]);

        $response = $this->generateBaseReponse($obj->getUpdatedAt());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $result = $this->serializer->serialize(
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
        $this->logger->info('CASE API: Returned object', ['type' => get_class($obj), 'id' => $obj->getIdentifier()]);

        $response = $this->generateBaseReponse(new \DateTime());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $collection = explode('/', $request->getPathInfo())[4];

        $result = $this->serializer->serialize(
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
