<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddExternalDocCommand;
use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\DeleteItemWithChildrenCommand;
use App\Command\Framework\UpdateTreeItemsCommand;
use App\Entity\ChangeEntry;
use App\Entity\Framework\ObjectLock;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Form\Type\LsDocListType;
use App\Entity\User\User;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Util\Compare;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Editor Tree controller.
 *
 * @Route("/cftree")
 */
class DocTreeController extends AbstractController
{
    use CommandDispatcherTrait;

    private const ETAG_SEED = '1';

    /**
     * @var ClientInterface
     */
    private $guzzleJsonClient;

    private $caseNetworkClientId;
    private $caseNetworkClientSecret;
    private $caseNetworkScope;
    private $caseNetworkTokenEndpoint;

    /**
     * @var PdoAdapter
     */
    private $externalDocCache;

    public function __construct(ClientInterface $guzzleJsonClient, PdoAdapter $externalDocCache, ?string $caseNetworkClientId, ?string $caseNetworkClientSecret, ?string $caseNetworkScope, ?string $caseNetworkTokenEndpoint)
    {
        $this->guzzleJsonClient = $guzzleJsonClient;
        $this->externalDocCache = $externalDocCache;
        $this->caseNetworkClientId = $caseNetworkClientId;
        $this->caseNetworkClientSecret = $caseNetworkClientSecret;
        $this->caseNetworkTokenEndpoint = $caseNetworkTokenEndpoint;
        $this->caseNetworkScope = $caseNetworkScope;
    }

    /**
     * @Route("/doc/{slug}", name="doc_tree_view", methods={"GET"}, requirements={"slug"="[a-zA-Z0-9.-]+"}, defaults={"lsItemId"=null})
     * @Route("/doc/{slug}/av", name="doc_tree_view_av", methods={"GET"}, requirements={"slug"="[a-zA-Z0-9.-]+"}, defaults={"lsItemId"=null})
     * @Route("/doc/{slug}/lv", name="doc_tree_view_log", methods={"GET"}, requirements={"slug"="[a-zA-Z0-9.-]+"}, defaults={"lsItemId"=null})
     * @Route("/doc/{slug}/{assocGroup}", name="doc_tree_view_ag", methods={"GET"}, requirements={"slug"="[a-zA-Z0-9.-]+"}, defaults={"lsItemId"=null})
     * @Entity("lsDoc", expr="repository.findOneBySlug(slug)")
     * @Template()
     */
    public function viewAction(LsDoc $lsDoc, AuthorizationCheckerInterface $authChecker, ?UserInterface $user = null, $lsItemId = null, $assocGroup = null): array
    {
        $em = $this->getDoctrine()->getManager();

        // Get all association groups (for all documents);
        // we need groups for other documents if/when we show a document on the right side
        $lsDefAssociationGroupings = $em->getRepository(LsDefAssociationGrouping::class)->findAll();

        $assocTypes = [];
        $inverseAssocTypes = [];
        foreach (LsAssociation::allTypes() as $type) {
            $assocTypes[] = $type;
            $inverseAssocTypes[] = LsAssociation::inverseName($type);
        }

        $editorRights = $authChecker->isGranted('edit', $lsDoc);

        $ret = [
            'lsDoc' => $lsDoc,
            'lsDocId' => $lsDoc->getId(),
            'lsDocTitle' => $lsDoc->getTitle(),

            'editorRights' => $editorRights,
            'isDraft' => $lsDoc->isDraft(),
            'isAdopted' => $lsDoc->isAdopted(),
            'isDeprecated' => $lsDoc->isDeprecated(),
            'manageEditorsRights' => $authChecker->isGranted('manage_editors', $lsDoc),
            'createRights' => $authChecker->isGranted('create', 'lsdoc'),

            'lsItemId' => $lsItemId,
            'assocGroup' => $assocGroup,
            'assocTypes' => $assocTypes,
            'inverseAssocTypes' => $inverseAssocTypes,
            'assocGroups' => $lsDefAssociationGroupings,
        ];

        if ($editorRights) {
            // get form field for selecting a document (for tree2)
            $docList = $this->createForm(LsDocListType::class, null, ['ajax' => false])->createView();
            $ret['docList'] = $docList;

            $ret['lsDocs'] = $this->getViewableDocList($authChecker);
            $ret['locks'] = $this->getLocks($lsDoc, $user);
        }

        return $ret;
    }

    /**
     * @Route("/remote", name="doc_tree_remote_view", methods={"GET"})
     */
    public function viewRemoteAction(): Response
    {
        $assocTypes = [];
        $inverseAssocTypes = [];
        foreach (LsAssociation::allTypes() as $type) {
            $assocTypes[] = $type;
            $inverseAssocTypes[] = LsAssociation::inverseName($type);
        }

        return $this->render('framework/doc_tree/view.html.twig', [
            'lsDoc' => '',
            'lsDocId' => 'url',
            'lsDocTitle' => 'Remote Framework',

            'editorRights' => false,
            'manageEditorsRights' => false,
            'createRights' => false,

            'lsItemId' => null,
            'assocGroup' => null,
            'docList' => '',
            'assocTypes' => $assocTypes,
            'inverseAssocTypes' => $inverseAssocTypes,
            'assocGroups' => [],
            'lsDocs' => [],
        ]);
    }


    /**
     * Export a CFPackage in a special json format designed for efficiently loading the package's data to the OpenSALT doctree client.
     *
     * @Route("/docexport/{id}.json", name="doctree_cfpackage_export", methods={"GET"})
     */
    public function exportAction(Request $request, LsDoc $lsDoc): JsonResponse
    {
        $response = new JsonResponse();

        $changeRepo = $this->getDoctrine()->getRepository(ChangeEntry::class);
        $lastChange = $changeRepo->getLastChangeTimeForDoc($lsDoc);

        $lastModified = $lsDoc->getUpdatedAt();
        if (false !== $lastChange && null !== $lastChange['changed_at']) {
            $lastModified = new \DateTime($lastChange['changed_at'], new \DateTimeZone('UTC'));
        }
        $response->setEtag(md5($lastModified->format('U.u').self::ETAG_SEED), true);
        $response->setLastModified($lastModified);
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->setExpires(\DateTime::createFromFormat('U', $lastModified->format('U'))->sub(new \DateInterval('PT1S')));
        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');

        if ($response->isNotModified($request)) {
            return $response;
        }

        $items = $this->getDoctrine()->getRepository(LsDoc::class)->findItemsForExportDoc($lsDoc);
        $associations = $this->getDoctrine()->getRepository(LsDoc::class)->findAssociationsForExportDoc($lsDoc);
        $assocGroups = $this->getDoctrine()->getRepository(LsDefAssociationGrouping::class)->findBy(['lsDoc' => $lsDoc]);
        $associatedDocs = array_merge(
            $lsDoc->getExternalDocs(),
            $this->getDoctrine()->getRepository(LsDoc::class)->findAssociatedDocs($lsDoc)
        );

        $docAttributes = [
            'baseDoc' => $lsDoc->getAttribute('baseDoc'),
            'associatedDocs' => $associatedDocs,
        ];

        $itemTypes = [];
        foreach ($items as $item) {
            if (!empty($item['itemType'])) {
                $itemTypes[$item['itemType']['code']] = $item['itemType'];
            }
        }

        $arr = [
            'lsDoc' => $lsDoc,
            'docAttributes' => $docAttributes,
            'items' => $items,
            'associations' => $associations,
            'itemTypes' => $itemTypes,
            'subjects' => $lsDoc->getSubjects(),
            'concepts' => [],
            'licences' => [$lsDoc->getLicence()],
            'assocGroups' => $assocGroups,
        ];

        $response->setContent($this->renderView('framework/doc_tree/export.json.twig', $arr));

        // This is called to retrieve a response by other methods, so cannot use a template
        return $response;
    }

    /**
     * Retrieve a CFPackage from the given document identifier, then use exportAction to export it.
     *
     * @Route("/retrievedocument/{id}", name="doctree_retrieve_document", methods={"GET"})
     * @Route("/retrievedocument/url", name="doctree_retrieve_document_by_url", methods={"GET"}, defaults={"id"=null})
     *
     * @throws \Exception
     */
    public function retrieveDocumentAction(Request $request, ?LsDoc $lsDoc = null): Response
    {
        // $request could contain an id...
        if ($id = $request->query->get('id')) {
            // in this case it has to be a document on this OpenSALT instantiation
            return $this->respondWithDocumentById($request, $id);
        }

        // or an identifier...
        if (null !== $lsDoc && $identifier = $request->query->get('identifier')) {
            // first see if it's referencing a document on this OpenSALT instantiation
            return $this->respondWithDocumentByIdentifier($request, $identifier, $lsDoc);
        }

        // or a url...
        if ($url = $request->query->get('url')) {
            // try to load the url, noting that we should save a record of it in externalDocs if found
            return $this->exportExternalDocument($url, $lsDoc);
        }

        return new Response('Document not found.', Response::HTTP_NOT_FOUND);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function exportExternalDocument(string $url, ?LsDoc $lsDoc = null): Response
    {
        // Check the cache for the document
        $cache = $this->externalDocCache;
        $cacheDoc = $cache->getItem(rawurlencode($url));
        if ($cacheDoc->isHit()) {
            $document = $cacheDoc->get();
        } else {
            try {
                $document = $this->fetchExternalDocument($url);
            } catch (RequestException $e) {
                $error = $e->getResponse();

                throw new NotFoundHttpException($error->getReasonPhrase());
            } catch (\Exception $e) {
                $error = $e->getMessage();

                throw new NotFoundHttpException($error);
            }

            // Save document in cache for 30 minutes (arbitrary time period)
            $cacheDoc->set($document);
            $cacheDoc->expiresAfter(new \DateInterval('PT30M'));
            $cache->save($cacheDoc);
        }

        if (!empty($document)) {
            // if $lsDoc is not empty, get the document'document identifier and title and save to the $lsDoc'document externalDocs
            if (null !== $lsDoc) {
                $this->addExternalDocumentToDoc($url, $lsDoc, $document);
            }

            // now return the file
            $response = new Response(
                $document,
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/json',
                    'Pragma' => 'no-cache',
                ]
            );

            return $response;
        }

        // if we get to here, error
        return new Response('Document not found.', Response::HTTP_NOT_FOUND);
    }

    protected function isCaseUrl($url): bool
    {
        preg_match('|casenetwork\.imsglobal\.org|', $url, $matches);
        if (!empty($matches)) {
            return true;
        }

        return false;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function retrieveCaseNetworkBearerToken()
    {
        try {
            $response = $this->guzzleJsonClient->request(
                'POST',
                $this->caseNetworkTokenEndpoint,
                [
                    'timeout' => 6000,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json',
                        'User-Agent' => 'OpenSALT',
                    ],
                    'auth' => [$this->caseNetworkClientId, $this->caseNetworkClientSecret],
                    'http_errors' => true,
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'scope' => $this->caseNetworkScope,
                    ],
                ]
            );

            return json_decode($response->getBody(), false)->access_token;
        } catch (RequestException $e) {
            $message = $e->getHandlerContext();

            throw new NotFoundHttpException($message['error']);
        } catch (\Exception $e) {
            throw new NotFoundHttpException('Document not found.');
        }
    }

    /**
     * @Route("/item/{id}/details", name="doc_tree_item_details", methods={"GET"})
     * @Template()
     *
     * Note that this must come before viewItemAction for the url mapping to work properly.
     */
    public function treeItemDetailsAction(LsItem $lsItem): array
    {
        return ['lsItem' => $lsItem];
    }

    /**
     * @Route("/item/{id}.{_format}", name="doc_tree_item_view", methods={"GET"}, defaults={"_format"="html"})
     * @Route("/item/{id}/{assocGroup}.{_format}", name="doc_tree_item_view_ag", methods={"GET"}, defaults={"_format"="html"})
     */
    public function viewItemAction(LsItem $lsItem, ?string $assocGroup = null, string $_format = 'html'): Response
    {
        return $this->forward('App\Controller\Framework\DocTreeController:viewAction', ['slug' => $lsItem->getLsDoc()->getId(), '_format' => 'html', 'lsItemId' => $lsItem->getid(), 'assocGroup' => $assocGroup]);
    }

    /**
     * @Route("/render/{id}.{_format}", methods={"GET"}, defaults={"_format"="html"}, name="doctree_render_document")
     * @Template()
     *
     * PW: this is similar to the renderDocument function in the Editor directory, but different enough that I think it deserves a separate controller/view
     */
    public function renderDocumentAction(LsDoc $lsDoc, $_format = 'html'): array
    {
        $repo = $this->getDoctrine()->getRepository(LsDoc::class);

        $items = $repo->findAllChildrenArray($lsDoc);
        $haveParents = $repo->findAllItemsWithParentsArray($lsDoc);
        $topChildren = $repo->findTopChildrenIds($lsDoc);
        $parentsElsewhere = [];

        $orphaned = $items;
        foreach ($haveParents as $child) {
            // Not an orphan
            $id = $child['id'];
            if (!empty($orphaned[$id])) {
                unset($orphaned[$id]);
            }
        }

        foreach ($orphaned as $orphan) {
            foreach ($orphan['associations'] as $association) {
                if (LsAssociation::CHILD_OF === $association['type']) {
                    $parentsElsewhere[] = $orphan;
                    unset($orphaned[$orphan['id']]);
                }
            }
        }

        Compare::sortArrayByFields($orphaned, ['sequenceNumber', 'listEnumInSource', 'humanCodingScheme']);

        return [
            'topItemIds' => $topChildren,
            'lsDoc' => $lsDoc,
            'items' => $items,
            'parentsElsewhere' => $parentsElsewhere,
            'orphaned' => $orphaned,
        ];
    }

    /**
     * Deletes a LsItem entity, from the tree view.
     *
     * @Route("/item/{id}/delete/{includingChildren}", methods={"POST"}, name="lsitem_tree_delete", defaults={"includingChildren" = 0})
     * @Security("is_granted('edit', lsItem)")
     *
     * @throws \InvalidArgumentException
     */
    public function deleteItemAction(Request $request, LsItem $lsItem, int $includingChildren = 0): Response
    {
        $ajax = false;
        if ($request->isXmlHttpRequest()) {
            $ajax = true;
        }

        $lsDocSlug = $lsItem->getLsDoc()->getSlug();

        if (0 === $includingChildren) {
            $command = new DeleteItemCommand($lsItem);
            $this->sendCommand($command);
        } else {
            $command = new DeleteItemWithChildrenCommand($lsItem);
            $this->sendCommand($command);
        }

        if ($ajax) {
            return new Response($this->generateUrl('doc_tree_view', ['slug' => $lsDocSlug]), Response::HTTP_ACCEPTED);
        }

        return $this->redirectToRoute('doc_tree_view', ['slug' => $lsDocSlug]);
    }

    /**
     * Updates a set of items in the document from the tree view
     * Reorders are done by updating the listEnum fields of the items
     * This also does copies, of either single items or folders.
     * If we do a copy, the service returns an array of trees with the copied lsItemIds.
     * For other operations, we return an empty array.
     *
     * @Route("/doc/{id}/updateitems.{_format}", methods={"POST"}, name="doctree_update_items")
     * @Security("is_granted('edit', lsDoc)")
     * @Template()
     */
    public function updateItemsAction(Request $request, LsDoc $lsDoc, string $_format = 'json'): array
    {
        $command = new UpdateTreeItemsCommand($lsDoc, $request->request->get('lsItems'));
        $this->sendCommand($command);
        $rv = $command->getReturnValues();

        // get ids for new associations and items
        foreach ($rv as $lsItemId => $val) {
            if (!empty($rv[$lsItemId]['association'])) {
                $rv[$lsItemId]['assocId'] = $rv[$lsItemId]['association']->getId();
                unset($rv[$lsItemId]['association']);
            }

            if (!empty($rv[$lsItemId]['lsItem'])) {
                $rv[$lsItemId]['lsItemId'] = $rv[$lsItemId]['lsItem']->getId();
                unset($rv[$lsItemId]['lsItem']);
            }
        }

        return ['returnedItems' => $rv];
    }

    /**
     * Deletes a LsDefAssociationGrouping entity, ajax/treeview version.
     *
     * @Route("/assocgroup/{id}/delete", methods={"POST"}, name="lsdef_association_grouping_tree_delete")
     *
     * @throws \InvalidArgumentException
     */
    public function deleteAssocGroupAction(Request $request, LsDefAssociationGrouping $associationGrouping): Response
    {
        $command = new DeleteAssociationGroupCommand($associationGrouping);

        try {
            $this->sendCommand($command);
        } catch (\Exception $e) {
            if (preg_match('/FOREIGN KEY/', $e->getMessage())) {
                return new JsonResponse(['error' => ['message' => 'An association group may only be deleted if there are no associations in it.']], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['error' => ['message' => 'The association group could not be deleted.']], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse('OK', Response::HTTP_ACCEPTED);
    }

    /**
     * Create a response with a CFDocument.
     */
    protected function respondWithDocumentById(Request $request, int $id): Response
    {
        // in this case it has to be a document on this OpenSALT instantiation
        $newDoc = $this->getDoctrine()->getRepository(LsDoc::class)->find($id);
        if (empty($newDoc)) {
            // if document not found, error
            return new Response('Document not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->exportAction($request, $newDoc);
    }

    /**
     * Create a response with a CFDocument.
     */
    protected function respondWithDocumentByIdentifier(Request $request, string $identifier, LsDoc $lsDoc): Response
    {
        $newDoc = $this->getDoctrine()->getRepository(LsDoc::class)->findOneBy(['identifier' => $identifier]);
        if (null !== $newDoc) {
            return $this->exportAction($request, $newDoc);
        }

        // otherwise look in this doc's externalDocs
        // We could store, and check here, a global table of external documents that we could index by identifiers, instead of using document-specific associated docs. But it's not completely clear that would be an improvement.
        $externalDocs = $lsDoc->getExternalDocs();
        if (!empty($externalDocs[$identifier])) {
            // if we found it, load it, noting that we don't have to save a record of it in externalDocs (since it's already there)
            $response = $this->exportExternalDocument($externalDocs[$identifier]['url'], null);
            if (200 !== $response->getStatusCode()) {
                return $response;
            }

            $content = $response->getContent();
            $json = json_decode($content, true);
            $lastModified = new \DateTime($json['CFDocument']['lastChangeDateTime'], new \DateTimeZone('UTC'));

            $response->setEtag(md5($lastModified->format('U.u').self::ETAG_SEED), true);
            $response->setLastModified($lastModified);
            $response->setMaxAge(0);
            $response->setSharedMaxAge(0);
            $response->setExpires(\DateTime::createFromFormat('U', $lastModified->format('U'))->sub(new \DateInterval('PT1S')));
            $response->setPublic();
            $response->headers->addCacheControlDirective('must-revalidate');

            if ($response->isNotModified($request)) {
                return $response;
            }

            return $response;
        }

        // if not found in externalDocs, error
        return new Response('Document not found.', Response::HTTP_NOT_FOUND);
    }

    protected function addExternalDocumentToDoc(string $url, LsDoc $lsDoc, $document): void
    {
        $doc = json_decode($document, false);
        $title = $doc->CFDocument->title;
        $identifier = $doc->CFDocument->identifier;

        // if we found the identifier and title, save the ad
        if (!empty($identifier) && !empty($title)) {
            // see if the doc is already there; if so, we don't want to change the "autoLoad" parameter, but we should still update the title/url if necessary
            $externalDocs = $lsDoc->getExternalDocs();

            $autoLoad = 'false';
            if (!empty($externalDocs[$identifier])) {
                $autoLoad = $externalDocs[$identifier]['autoLoad'];
            }

            // if this is a new doc or anything has changed, save it
            if (empty($externalDocs[$identifier])
                || $externalDocs[$identifier]['autoLoad'] !== $autoLoad
                || $externalDocs[$identifier]['url'] !== $url
                || $externalDocs[$identifier]['title'] !== $title
            ) {
                $command = new AddExternalDocCommand($lsDoc, $identifier, $autoLoad, $url, $title);
                $this->sendCommand($command);
            }
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function fetchExternalDocument(string $url): string
    {
        $headers = [
            'Accept' => 'application/json',
        ];
        $headers = $this->addAuthentication($url, $headers);

        $extDoc = $this->guzzleJsonClient->request(
            'GET',
            $url,
            [
                'timeout' => 60,
                'headers' => $headers,
                'http_errors' => true,
            ]
        );

        return $extDoc->getBody()->getContents();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function addAuthentication(string $url, array $headers): array
    {
        // Check for CASE urls:
        if ($this->isCaseUrl($url)) {
            $headers = array_merge([
                'Authorization' => 'Bearer '.$this->retrieveCaseNetworkBearerToken(),
            ], $headers);
        }

        return $headers;
    }

    /**
     * Get a list of all documents viewable by the current user.
     */
    private function getViewableDocList(AuthorizationCheckerInterface $authChecker): array
    {
        $lsDocs = [];

        $docs = $this->getDoctrine()->getRepository(LsDoc::class)->findBy([], ['creator' => 'ASC', 'title' => 'ASC', 'adoptionStatus' => 'ASC']);
        /** @var LsDoc $doc */
        foreach ($docs as $doc) {
            // Optimization: All but "Private Draft" are viewable to everyone, only auth check "Private Draft"
            if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT !== $doc->getAdoptionStatus() || $authChecker->isGranted('view', $doc)) {
                $lsDocs[] = $doc;
            }
        }

        return $lsDocs;
    }

    /**
     * Get a list of all locks for the document.
     */
    private function getLocks(LsDoc $lsDoc, ?UserInterface $user): array
    {
        $docLocks = ['docs' => ['_' => ''], 'items' => ['_' => '']];
        if ($user instanceof User) {
            $locks = $this->getDoctrine()->getRepository(ObjectLock::class)->findDocLocks($lsDoc);
            foreach ($locks as $lock) {
                $expiry = false;
                if ($lock->getUser() !== $user) {
                    $expiry = (int) $lock->getTimeout()->add(new \DateInterval('PT30S'))->format('Uv');
                }

                if (LsDoc::class === $lock->getObjectType()) {
                    $docLocks['docs'][$lock->getObjectId()] = $expiry;
                }

                if (LsItem::class === $lock->getObjectType()) {
                    $docLocks['items'][$lock->getObjectId()] = $expiry;
                }
            }
        }

        return $docLocks;
    }
}
