<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddExternalDocCommand;
use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\DeleteItemWithChildrenCommand;
use App\Command\Framework\UpdateTreeItemsCommand;
use App\Entity\ChangeEntry;
use App\Entity\Framework\AssociationSubtype;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\ObjectLock;
use App\Entity\User\User;
use App\Form\Type\LsDocListType;
use App\Security\Permission;
use App\Util\Compare;
use Doctrine\Persistence\ManagerRegistry;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/cftree')]
class DocTreeController extends AbstractController
{
    use CommandDispatcherTrait;

    private const ETAG_SEED = '2';

    public function __construct(
        private readonly DoctrineDbalAdapter $externalDocCache,
        private readonly ManagerRegistry $managerRegistry,
        private readonly ?string $caseNetworkClientId,
        private readonly ?string $caseNetworkClientSecret,
        private readonly ?string $caseNetworkScope,
        private readonly ?string $caseNetworkTokenEndpoint,
    ) {
    }

    #[Route(path: '/doc/{slug}', name: 'doc_tree_view', requirements: ['slug' => '[a-zA-Z0-9.-]+'], defaults: ['lsItemId' => null], methods: ['GET'])]
    #[Route(path: '/doc/{slug}/av', name: 'doc_tree_view_av', requirements: ['slug' => '[a-zA-Z0-9.-]+'], defaults: ['lsItemId' => null], methods: ['GET'])]
    #[Route(path: '/doc/{slug}/lv', name: 'doc_tree_view_log', requirements: ['slug' => '[a-zA-Z0-9.-]+'], defaults: ['lsItemId' => null], methods: ['GET'])]
    #[Route(path: '/doc/{slug}/{assocGroup}', name: 'doc_tree_view_ag', requirements: ['slug' => '[a-zA-Z0-9.-]+'], defaults: ['lsItemId' => null], methods: ['GET'])]
    public function view(#[MapEntity(expr: 'repository.findOneBySlug(slug)')] LsDoc $lsDoc, AuthorizationCheckerInterface $authChecker, #[CurrentUser] ?User $user, ?string $lsItemId = null, ?string $assocGroup = null): Response
    {
        $em = $this->managerRegistry->getManager();

        // Get all association groups (for all documents);
        // we need groups for other documents if/when we show a document on the right side
        $lsDefAssociationGroupings = $em->getRepository(LsDefAssociationGrouping::class)->findAll();

        $assocSubTypes = $em->getRepository(AssociationSubtype::class)->findAll();
        $assocFilterTypes = [];
        $assocTypes = [];
        $inverseAssocTypes = [];
        foreach (LsAssociation::allTypes() as $type) {
            $assocFilterTypes[] = $type;
            $assocTypes[] = $type;
            $inverseAssocTypes[] = LsAssociation::inverseName($type);
            foreach ($assocSubTypes as $subtype) {
                if ($type === $subtype->getParentType()) {
                    $assocFilterTypes[] = '-'.$subtype->getName();
                    if (AssociationSubtype::DIR_INVERSE !== $subtype->getDirection()) {
                        $assocTypes[] = '-'.$subtype->getName();
                        $inverseAssocTypes[] = null;
                    }
                    if (AssociationSubtype::DIR_FORWARD !== $subtype->getDirection()) {
                        $assocTypes[] = null;
                        $inverseAssocTypes[] = '-'.$subtype->getName();
                    }
                }
            }
        }

        $editorRights = $authChecker->isGranted(Permission::FRAMEWORK_EDIT, $lsDoc);

        $ret = [
            'lsDoc' => $lsDoc,
            'lsDocId' => $lsDoc->getId(),
            'lsDocTitle' => $lsDoc->getTitle(),

            'editorRights' => $editorRights,
            'isDraft' => $lsDoc->isDraft(),
            'isAdopted' => $lsDoc->isAdopted(),
            'isDeprecated' => $lsDoc->isDeprecated(),
            'manageEditorsRights' => $authChecker->isGranted(Permission::MANAGE_EDITORS, $lsDoc),
            'createRights' => $authChecker->isGranted(Permission::FRAMEWORK_CREATE),

            'lsItemId' => $lsItemId,
            'assocGroup' => $assocGroup,
            'assocFilterTypes' => $assocFilterTypes,
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

        return $this->render('framework/doc_tree/view.html.twig', $ret);
    }

    #[Route(path: '/remote', name: 'doc_tree_remote_view', methods: ['GET'])]
    public function viewRemote(): Response
    {
        $assocSubTypes = $this->managerRegistry->getRepository(AssociationSubtype::class)->findAll();
        $assocFilterTypes = [];
        $assocTypes = [];
        $inverseAssocTypes = [];
        foreach (LsAssociation::allTypes() as $type) {
            $assocFilterTypes[] = $type;
            $assocTypes[] = $type;
            $inverseAssocTypes[] = LsAssociation::inverseName($type);
            foreach ($assocSubTypes as $subtype) {
                $assocFilterTypes[] = '-'.$subtype->getName();
                if ($type === $subtype->getParentType()) {
                    if (AssociationSubtype::DIR_INVERSE !== $subtype->getDirection()) {
                        $assocTypes[] = '-'.$subtype->getName();
                        $inverseAssocTypes[] = null;
                    }
                    if (AssociationSubtype::DIR_FORWARD !== $subtype->getDirection()) {
                        $assocTypes[] = null;
                        $inverseAssocTypes[] = '-'.$subtype->getName();
                    }
                }
            }
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
            'assocFilterTypes' => $assocFilterTypes,
            'assocTypes' => $assocTypes,
            'inverseAssocTypes' => $inverseAssocTypes,
            'assocGroups' => [],
            'lsDocs' => [],
        ]);
    }

    /**
     * Export a CFPackage in a special json format designed for efficiently loading the package's data to the OpenSALT doctree client.
     */
    #[Route(path: '/docexport/{id}.json', name: 'doctree_cfpackage_export', methods: ['GET'])]
    public function export(Request $request, LsDoc $lsDoc): JsonResponse
    {
        $response = new JsonResponse();

        $changeRepo = $this->managerRegistry->getRepository(ChangeEntry::class);
        $lastChange = $changeRepo->getLastChangeTimeForDoc($lsDoc);

        $lastModified = $lsDoc->getUpdatedAt();
        if (null !== ($lastChange['changed_at'] ?? null)) {
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

        $items = $this->managerRegistry->getRepository(LsDoc::class)->findItemsForExportDoc($lsDoc);
        $associations = $this->managerRegistry->getRepository(LsDoc::class)->findAssociationsForExportDoc($lsDoc);
        $groupIds = [];
        foreach ($associations as $association) {
            if (($association['group']['identifier'] ?? null) !== null) {
                $groupIds[$association['group']['identifier']] = 1;
            }
        }
        $assocGroups = $this->managerRegistry->getRepository(LsDefAssociationGrouping::class)->findByIdentifiers(array_keys($groupIds));
        $associatedDocs = array_merge(
            $lsDoc->getExternalDocs(),
            $this->managerRegistry->getRepository(LsDoc::class)->findAssociatedDocs($lsDoc)
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
     * Retrieve a CFPackage from the given document identifier, then use export() to export it.
     *
     * @throws \Exception
     */
    #[Route(path: '/retrievedocument/{id}', name: 'doctree_retrieve_document', methods: ['GET'])]
    #[Route(path: '/retrievedocument/url', name: 'doctree_retrieve_document_by_url', defaults: ['id' => null], methods: ['GET'])]
    public function retrieveDocument(Request $request, ?LsDoc $lsDoc = null): Response
    {
        ini_set('memory_limit', '1G');

        // $request could contain an id...
        if ($id = $request->query->get('id')) {
            // in this case it has to be a document on this OpenSALT instantiation
            return $this->respondWithDocumentById($request, (int) $id);
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
     * @throws GuzzleException
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

        if (empty($document)) {
            throw new NotFoundHttpException('Document not found.');
        }

        // if $lsDoc is not empty, get the document'document identifier and title and save to the $lsDoc'document externalDocs
        if (null !== $lsDoc) {
            $this->addExternalDocumentToDoc($url, $lsDoc, $document);
        }

        // now return the file
        return new Response(
            $document,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/json',
                'Pragma' => 'no-cache',
            ]
        );
    }

    protected function isCaseNetworkUrl(string $url): bool
    {
        preg_match('|casenetwork\.imsglobal\.org|', $url, $matches);
        if (!empty($matches)) {
            return true;
        }

        return false;
    }

    protected function retrieveCaseNetworkBearerToken(): string
    {
        try {
            $jsonClient = new Client();
            $response = $jsonClient->request(
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

            return json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR)->access_token;
        } catch (RequestException $e) {
            $message = $e->getHandlerContext();

            throw new NotFoundHttpException($message['error']);
        } catch (\Exception) {
            throw new NotFoundHttpException('Document not found.');
        }
    }

    /**
     * Note that this must come before viewItem for the url mapping to work properly.
     */
    #[Route(path: '/item/{id}/details', name: 'doc_tree_item_details', methods: ['GET'])]
    public function treeItemDetails(LsItem $lsItem): Response
    {
        return $this->render('framework/doc_tree/tree_item_details.html.twig', ['lsItem' => $lsItem]);
    }

    #[Route(path: '/item/{id}.{_format}', name: 'doc_tree_item_view', defaults: ['_format' => 'html'], methods: ['GET'])]
    #[Route(path: '/item/{id}/{assocGroup}.{_format}', name: 'doc_tree_item_view_ag', defaults: ['_format' => 'html'], methods: ['GET'])]
    public function viewItem(LsItem $lsItem, ?string $assocGroup = null, string $_format = 'html'): Response
    {
        return $this->forward('App\Controller\Framework\DocTreeController::view', ['slug' => $lsItem->getLsDoc()->getId(), '_format' => 'html', 'lsItemId' => $lsItem->getId(), 'assocGroup' => $assocGroup]);
    }

    /**
     * PW: this is similar to the renderDocument function in the Editor directory, but different enough that I think it deserves a separate controller/view
     */
    #[Route(path: '/render/{id}.{_format}', name: 'doctree_render_document', defaults: ['_format' => 'json'], methods: ['GET'])]
    public function renderDocument(LsDoc $lsDoc, string $_format = 'json'): Response
    {
        $repo = $this->managerRegistry->getRepository(LsDoc::class);

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

        return $this->render('framework/doc_tree/render_document.json.twig', [
            'topItemIds' => $topChildren,
            'lsDoc' => $lsDoc,
            'items' => $items,
            'parentsElsewhere' => $parentsElsewhere,
            'orphaned' => $orphaned,
        ]);
    }

    /**
     * Deletes a LsItem entity, from the tree view.
     *
     * @throws \InvalidArgumentException
     */
    #[Route(path: '/item/{id}/delete/{includingChildren}', name: 'lsitem_tree_delete', defaults: ['includingChildren' => 0], methods: ['POST'])]
    #[IsGranted(Permission::ITEM_EDIT, 'lsItem')]
    public function deleteItem(Request $request, LsItem $lsItem, int $includingChildren = 0): Response
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
     */
    #[Route(path: '/doc/{id}/updateitems.{_format}', name: 'doctree_update_items', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'lsDoc')]
    public function updateItems(Request $request, LsDoc $lsDoc, string $_format = 'json'): Response
    {
        $lsItems = $request->request->all('lsItems');
        $command = new UpdateTreeItemsCommand($lsDoc, $lsItems);
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

        return $this->render('framework/doc_tree/update_items.json.twig', ['returnedItems' => $rv]);
    }

    /**
     * Deletes a LsDefAssociationGrouping entity, ajax/treeview version.
     *
     * @throws \InvalidArgumentException
     */
    #[Route(path: '/assocgroup/{id}/delete', name: 'lsdef_association_grouping_tree_delete', methods: ['POST'])]
    public function deleteAssocGroup(LsDefAssociationGrouping $associationGrouping): Response
    {
        $command = new DeleteAssociationGroupCommand($associationGrouping);

        try {
            $this->sendCommand($command);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'FOREIGN KEY')) {
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
        $newDoc = $this->managerRegistry->getRepository(LsDoc::class)->find($id);
        if (empty($newDoc)) {
            // if document not found, error
            return new Response('Document not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->export($request, $newDoc);
    }

    /**
     * Create a response with a CFDocument.
     */
    protected function respondWithDocumentByIdentifier(Request $request, string $identifier, LsDoc $lsDoc): Response
    {
        $newDoc = $this->managerRegistry->getRepository(LsDoc::class)->findOneBy(['identifier' => $identifier]);
        if (null !== $newDoc) {
            return $this->export($request, $newDoc);
        }

        // otherwise look in this doc's externalDocs
        // We could store, and check here, a global table of external documents that we could index by identifiers, instead of using document-specific associated docs. But it's not completely clear that would be an improvement.
        $externalDocs = $lsDoc->getExternalDocs();
        if (!empty($externalDocs[$identifier])) {
            // if we found it, load it, noting that we don't have to save a record of it in externalDocs (since it's already there)
            $response = $this->exportExternalDocument($externalDocs[$identifier]['url'], null);
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                return $response;
            }

            $content = $response->getContent();
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
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
        $doc = json_decode($document, false, 512, JSON_THROW_ON_ERROR);
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

    protected function fetchExternalDocument(string $url): string
    {
        $headers = [
            'Accept' => 'application/vnd.opensalt+json, application/json;q=0.9, text/plain;q=0.2, */*;q=0.1',
        ];
        $headers = $this->addAuthentication($url, $headers);

        $jsonClient = new Client();
        $extDoc = $jsonClient->request(
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

    protected function addAuthentication(string $url, array $headers): array
    {
        // Check for CASE Network urls
        if ($this->isCaseNetworkUrl($url)) {
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

        $docs = $this->managerRegistry->getRepository(LsDoc::class)->findBy([], ['creator' => 'ASC', 'title' => 'ASC', 'adoptionStatus' => 'ASC']);
        /** @var LsDoc $doc */
        foreach ($docs as $doc) {
            // Optimization: All but "Private Draft" are viewable to everyone, only auth check "Private Draft"
            if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT !== $doc->getAdoptionStatus() || $authChecker->isGranted(Permission::FRAMEWORK_VIEW, $doc)) {
                $lsDocs[] = $doc;
            }
        }

        return $lsDocs;
    }

    /**
     * Get a list of all locks for the document.
     */
    private function getLocks(LsDoc $lsDoc, #[CurrentUser] ?User $user): array
    {
        $docLocks = ['docs' => ['_' => ''], 'items' => ['_' => '']];
        if ($user instanceof User) {
            $locks = $this->managerRegistry->getRepository(ObjectLock::class)->findDocLocks($lsDoc);
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
