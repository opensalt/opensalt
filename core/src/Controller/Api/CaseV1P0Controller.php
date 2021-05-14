<?php

namespace App\Controller\Api;

use App\Entity\ChangeEntry;
use App\Entity\Framework\CaseApiInterface;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\ChangeEntryRepository;
use App\Repository\Framework\CfDocQuery;
use App\Repository\Framework\LsDocRepository;
use App\Service\LoggerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/ims/case/v1p0")
 */
class CaseV1P0Controller extends AbstractController
{
    use LoggerTrait;

    public function __construct(
        private SerializerInterface $symfonySerializer,
        private string $assetsVersion,
    ) {
    }

    /**
     * @Route("/CFDocuments.{_format}", name="api_v1p0_cfdocuments", methods={"GET"}, defaults={"_format"="json"})
     */
    public function getPublicCfDocumentsAction(Request $request): Response
    {
        $limit = (int) $request->query->get('limit', '100');
        $offset = (int) $request->query->get('offset', '0');
        $sort = (string) $request->query->get('sort', null);
        $orderBy = (string) $request->query->get('orderBy', 'asc');

        /*
        $filter = $request->query->get('filter', '');
        $fields = $request->query->get('fields', []);
        */

        $query = new CfDocQuery();
        $query->limit = 100000;
        $query->offset = 0;
        $query->sort = $sort;
        $query->orderBy = $orderBy;

        $repo = $this->getDoctrine()->getRepository(LsDoc::class);
        $results = $repo->findAllNonPrivate($query);

        $docs = [];
        $docCount = 0;
        $lastModified = new \DateTime('now - 10 years');
        foreach ($results as $doc) {
            /** @var LsDoc $doc */
            if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT === $doc->getAdoptionStatus()) {
                continue;
            }
            if (null !== $doc->getMirroredFramework()) {
                // Do not show mirrored frameworks as available documents
                continue;
            }

            $docs[] = $doc;
            if ($docCount < $limit && $doc->getUpdatedAt() > $lastModified) {
                $lastModified = $doc->getUpdatedAt();
            }
            ++$docCount;
        }

        $docs = array_slice($docs, $offset, $limit);

        $this->info('CASE API: getPublicCfDocuments', []);

        $response = $this->generateBaseResponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $groups = ['default', 'LsDoc'];
        if ('updatedAt' === $sort) {
            $groups[] = 'updatedAt';
        }
        $response->setContent(
            $this->symfonySerializer->serialize(['CFDocuments' => $docs], 'json', [
                'groups' => $groups,
                'json_encode_options' => \JSON_UNESCAPED_SLASHES,
            ])
        );
        $response->headers->set('X-Total-Count', (string) $docCount);

        return $response;
    }

    /**
     * @Route("/CFPackages/{id}.{_format}", name="api_v1p0_cfpackage", methods={"GET"}, defaults={"_format"="json"})
     * @Entity("obj", expr="repository.findOneByIdentifier(id)")
     */
    public function getCfPackageAction(Request $request, LsDoc $obj): Response
    {
        $id = $obj->getIdentifier();

        $this->info('CASE API: package returned', ['id' => $id]);

        /** @var ChangeEntryRepository $changeRepo */
        $changeRepo = $this->getDoctrine()->getRepository(ChangeEntry::class);
        $lastChange = $changeRepo->getLastChangeTimeForDoc($obj);

        $lastModified = $obj->getUpdatedAt();
        if (null !== ($lastChange['changed_at'] ?? null)) {
            $lastModified = new \DateTime($lastChange['changed_at'], new \DateTimeZone('UTC'));
        }
        $response = $this->generateBaseResponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent(
            $this->symfonySerializer->serialize($obj, 'json', [
                'groups' => ['default', 'CfPackage'],
                'json_encode_options' => \JSON_UNESCAPED_SLASHES,
                'generate-package' => 'v1p0',
            ])
        );

        return $response;
    }

    /**
     * @Route("/CFItemAssociations/{id}.{_format}", name="api_v1p0_cfitemassociations", methods={"GET"}, defaults={"_format"="json"})
     * @Route("/CFItems/{id}/associations.{_format}", name="api_v1p0_cfitemassociations2", methods={"GET"}, defaults={"_format"="json"})
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

        $this->info('CASE API: item associations returned', ['id' => $id]);

        $response = $this->generateBaseResponse($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent(
            $this->symfonySerializer->serialize([
                'CFItem' => $item,
                'CFAssociations' => $associations,
            ], 'json', [
                'groups' => ['default', 'LsItem', 'LsAssociation'],
                'json_encode_options' => \JSON_UNESCAPED_SLASHES,
            ])
        );
        $response->headers->set('X-Total-Count', [(string) count($associations)]);

        return $response;
    }

    /**
     * @Route("/CFAssociationGroupings/{id}.{_format}", name="api_v1p0_cfassociationgrouping", methods={"GET"}, defaults={"class"="App\Entity\Framework\LsDefAssociationGrouping", "_format"="json"})
     * @Route("/CFAssociations/{id}.{_format}", name="api_v1p0_cfassociation", methods={"GET"}, defaults={"class"="App\Entity\Framework\LsAssociation", "_format"="json"})
     * @Route("/CFDocuments/{id}.{_format}", name="api_v1p0_cfdocument", methods={"GET"}, defaults={"class"="App\Entity\Framework\LsDoc", "_format"="json"})
     * @Route("/CFItems/{id}.{_format}", name="api_v1p0_cfitem", methods={"GET"}, defaults={"class"="App\Entity\Framework\LsItem", "_format"="json"})
     * @Route("/CFLicenses/{id}.{_format}", name="api_v1p0_cflicense", methods={"GET"}, defaults={"class"="App\Entity\Framework\LsDefLicence", "_format"="json"})
     * @Route("/CFRubrics/{id}.{_format}", name="api_v1p0_cfrubric", methods={"GET"}, defaults={"class"="App\Entity\Framework\CfRubric", "_format"="json"})
     * @Route("/CFRubricCriteria/{id}.{_format}", name="api_v1p0_cfrubriccriterion", methods={"GET"}, defaults={"class"="App\Entity\Framework\CfRubricCriterion", "_format"="json"})
     * @Route("/CFRubricCriterionLevels/{id}.{_format}", name="api_v1p0_cfrubriccriterionlevel", methods={"GET"}, defaults={"class"="App\Entity\Framework\CfRubricCriterionLevel", "_format"="json"})
     */
    public function getObjectAction(Request $request, LsDocRepository $repo, string $class, string $id): Response
    {
        $obj = $repo->apiFindOneByClassIdentifier(['class' => $class, 'id' => $id]);

        return $this->generateObjectResponse($request, $obj);
    }

    /**
     * @Route("/CFConcepts/{id}.{_format}", name="api_v1p0_cfconcept", methods={"GET"}, defaults={"class"="App\Entity\Framework\LsDefConcept", "_format"="json"})
     * @Route("/CFItemTypes/{id}.{_format}", name="api_v1p0_cfitemtype", methods={"GET"}, defaults={"class"="App\Entity\Framework\LsDefItemType", "_format"="json"})
     * @Route("/CFSubjects/{id}.{_format}", name="api_v1p0_cfsubject", methods={"GET"}, defaults={"class"="App\Entity\Framework\LsDefSubject", "_format"="json"})
     */
    public function getObjectCollectionAction(Request $request, LsDocRepository $repo, string $class, string $id): Response
    {
        $obj = $repo->apiFindOneByClassIdentifier(['class' => $class, 'id' => $id]);

        return $this->generateObjectCollectionResponse($request, $obj);
    }

    /**
     * Generate a base response.
     */
    protected function generateBaseResponse(\DateTimeInterface $lastModified): Response
    {
        $response = new Response();

        $response->setEtag(md5($lastModified->format('U.u').$this->assetsVersion), true);
        $response->setLastModified($lastModified);
        $response->setMaxAge(60);
        $response->setSharedMaxAge(60);
        $response->setPublic();
        $response->setVary('Accept-Encoding, Accept', true);

        return $response;
    }

    /**
     * Generate a response for a single object.
     */
    protected function generateObjectResponse(Request $request, CaseApiInterface $obj): Response
    {
        $this->info('CASE API: Returned object', ['type' => get_class($obj), 'id' => $obj->getIdentifier()]);

        $response = $this->generateBaseResponse($obj->getUpdatedAt());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $className = substr(strrchr(get_class($obj), '\\'), 1);
        $response->setContent(
            $this->symfonySerializer->serialize($obj, 'json', [
                'groups' => ['default', $className],
                'json_encode_options' => \JSON_UNESCAPED_SLASHES,
            ])
        );

        return $response;
    }

    /**
     * Generate a response for a collection of objects.
     */
    protected function generateObjectCollectionResponse(Request $request, CaseApiInterface $obj): Response
    {
        $this->info('CASE API: Returned object', ['type' => get_class($obj), 'id' => $obj->getIdentifier()]);

        $response = $this->generateBaseResponse(new \DateTime());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $collection = explode('/', $request->getPathInfo())[4];

        $className = substr(strrchr(get_class($obj), '\\'), 1);
        $response->setContent(
            $this->symfonySerializer->serialize([$collection => [$obj]], 'json', [
                'groups' => ['default', $className],
                'json_encode_options' => \JSON_UNESCAPED_SLASHES,
            ])
        );

        return $response;
    }
}
