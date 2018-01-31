<?php

namespace CftfBundle\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddExternalDocCommand;
use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\DeleteItemWithChildrenCommand;
use App\Command\Framework\UpdateTreeItemsCommand;
use App\Entity\Framework\ObjectLock;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Form\Type\LsDocListType;
use Salt\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Util\Compare;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Editor Tree controller.
 *
 * @Route("/cftree")
 */
class DocTreeController extends Controller
{
    use CommandDispatcherTrait;

    /**
     * @Route("/doc/{slug}.{_format}", name="doc_tree_view", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/doc/{slug}/av.{_format}", name="doc_tree_view_av", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/doc/{slug}/lv.{_format}", name="doc_tree_view_log", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/doc/{slug}/{assocGroup}.{_format}", name="doc_tree_view_ag", defaults={"_format"="html", "lsItemId"=null})
     * @ParamConverter("lsDoc", class="CftfBundle:LsDoc", options={
     *     "repository_method" = "findOneBySlug",
     *     "mapping": {"slug": "slug"},
     *     "map_method_signature" = true
     * })
     * @Method({"GET"})
     * @Template()
     */
    public function viewAction(LsDoc $lsDoc, UserInterface $user = null, $_format = 'html', $lsItemId = null, $assocGroup = null)
    {
        // get form field for selecting a document (for tree2)
        $form = $this->createForm(LsDocListType::class, null, ['ajax' => false]);

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

        $authChecker = $this->get('security.authorization_checker');
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
            'docList' => $form->createView(),
            'assocTypes' => $assocTypes,
            'inverseAssocTypes' => $inverseAssocTypes,
            'assocGroups' => $lsDefAssociationGroupings,
        ];

        if ($editorRights) {
            // get list of all documents
            $docs = $em->getRepository(LsDoc::class)->findBy([], ['creator'=>'ASC', 'title'=>'ASC', 'adoptionStatus'=>'ASC']);
            $lsDocs = [];
            foreach ($docs as $doc) {
                if ($authChecker->isGranted('view', $doc)) {
                    $lsDocs[] = $doc;
                }
            }
            $ret['lsDocs'] = $lsDocs;

            $docLocks = ['docs' => ['_' => ''], 'items' => ['_' => '']];
            if ($user instanceof User) {
                $locks = $em->getRepository(ObjectLock::class)->findDocLocks($lsDoc);
                foreach ($locks as $lock) {
                    if ($lock->getUser() === $user) {
                        $expiry = false;
                    } else {
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
            $ret['locks'] = $docLocks;
        }

        return $ret;
    }

    /**
     * @Route("/remote", name="doc_tree_remote_view")
     * @Method({"GET"})
     */
    public function viewRemoteAction()
    {
        $assocTypes = [];
        $inverseAssocTypes = [];
        foreach (LsAssociation::allTypes() as $type) {
            $assocTypes[] = $type;
            $inverseAssocTypes[] = LsAssociation::inverseName($type);
        }

        $arr = [
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
            'lsDocs' => []
        ];

        return new Response($this->renderView('CftfBundle:DocTree:view.html.twig', $arr));
    }

///////////////////////////////////////////////

    /**
     * Export a CFPackage in a special json format designed for efficiently loading the package's data to the OpenSALT doctree client
     *
     * @Route("/docexport/{id}.json", name="doctree_cfpackage_export")
     * @Method("GET")
     */
    public function exportAction(Request $request, LsDoc $lsDoc)
    {
        $response = new Response();

        $lastModified = $lsDoc->getUpdatedAt();
        $response->setEtag(md5($lastModified->format('U')));
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
        $assocGroups = $this->getDoctrine()->getRepository(LsDefAssociationGrouping::class)->findBy(['lsDoc'=>$lsDoc]);
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
            'licences' => [],
            'assocGroups' => $assocGroups,
        ];

        $response->setContent($this->renderView('CftfBundle:DocTree:export.json.twig', $arr));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Retrieve a CFPackage from the given document identifier, then use exportAction to export it
     *
     * @Route("/retrievedocument/{id}", name="doctree_retrieve_document")
     * @Route("/retrievedocument/url", name="doctree_retrieve_document_by_url", defaults={"id"=null})
     * @Method("GET")
     */
    public function retrieveDocumentAction(Request $request, ?LsDoc $lsDoc = null)
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

    protected function exportExternalDocument($url, ?LsDoc $lsDoc = null) {
        // Check the cache for the document
        $cache = $this->get('salt.cache.external_docs');
        $cacheDoc = $cache->getItem(rawurlencode($url));
        if ($cacheDoc->isHit()) {
            $s = $cacheDoc->get();
        } else {
            // first check to see if this url returns a valid document (function taken from notes of php file_exists)
            $client = $this->get('csa_guzzle.client.json');
            $extDoc = $client->request(
                'GET',
                $url,
                [
                    'timeout' => 60,
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'http_errors' => false,
                ]
            );
            if ($extDoc->getStatusCode() === 404) {
                return new Response(
                    'Document not found.',
                    Response::HTTP_NOT_FOUND
                );
            }
            if ($extDoc->getStatusCode() !== 200) {
                return new Response(
                    $extDoc->getReasonPhrase(),
                    $extDoc->getStatusCode()
                );
            }

            // file exists, so get it
            $s = $extDoc->getBody()->getContents();

            // Save document in cache for 30 minutes (arbitrary time period)
            $cacheDoc->set($s);
            $cacheDoc->expiresAfter(new \DateInterval('PT30M'));
            $cache->save($cacheDoc);
        }
        if (!empty($s)) {
            // if $lsDoc is not empty, get the document's identifier and title and save to the $lsDoc's externalDocs
            if (null !== $lsDoc) {
                // This might not be the most elegant way to get  way to get the doc's identifier and id, but it should work
                $identifier = '';
                if (preg_match("/\"identifier\"\s*:\s*\"(.+?)\"/", $s, $matches)) {
                    $identifier = $matches[1];
                }

                $title = '';
                if (preg_match("/\"title\"\s*:\s*\"([\s\S]+?)\"/", $s, $matches)) {
                    $title = $matches[1];
                }

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

            // now return the file
            $response = new Response($s);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        }

        // if we get to here, error
        return new Response('Document not found.', Response::HTTP_NOT_FOUND);
        // example urls:
        // http://127.0.0.1:3000/app_dev.php/uri/731cf3e4-43a2-4aa0-b2a7-87a49dac5374.json
        // https://salt-staging.edplancms.com/uri/b821b70d-d46c-519b-b5cc-ca2260fc31f8.json
        // https://salt-staging.edplancms.com/cfpackage/doc/11/export
    }


    /**
     * @Route("/item/{id}/details", name="doc_tree_item_details")
     * @Method("GET")
     * @Template()
     *
     * Note that this must come before viewItemAction for the url mapping to work properly.
     *
     * @param \CftfBundle\Entity\LsItem $lsItem
     *
     * @return array
     */
    public function treeItemDetailsAction(LsItem $lsItem)
    {
        return ['lsItem'=>$lsItem];
    }

    /**
     * @Route("/item/{id}.{_format}", name="doc_tree_item_view", defaults={"_format"="html"})
     * @Route("/item/{id}/{assocGroup}.{_format}", name="doc_tree_item_view_ag", defaults={"_format"="html"})
     * @Method({"GET"})
     *
     * @param LsItem $lsItem
     * @param string $assocGroup
     * @param string $_format
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewItemAction(LsItem $lsItem, $assocGroup = null, $_format = 'html')
    {
        return $this->forward('CftfBundle:DocTree:view', ['slug' => $lsItem->getLsDoc()->getId(), '_format' => 'html', 'lsItemId' => $lsItem->getid(), 'assocGroup' => $assocGroup]);
    }



    /**
     * @Route("/render/{id}.{_format}", defaults={"_format"="html"}, name="doctree_render_document")
     * @Method("GET")
     * @Template()
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     * @param string $_format
     *
     * @return array
     *
     * PW: this is similar to the renderDocument function in the Editor directory, but different enough that I think it deserves a separate controller/view
     */
    public function renderDocumentAction(LsDoc $lsDoc, $_format = 'html')
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

        Compare::sortArrayByFields($orphaned, ['rank', 'listEnumInSource', 'humanCodingScheme']);

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
     * @Route("/item/{id}/delete/{includingChildren}", name="lsitem_tree_delete", defaults={"includingChildren" = 0})
     * @Method("POST")
     * @Security("is_granted('edit', lsItem)")
     *
     * @param Request $request
     * @param LsItem $lsItem
     * @param int $includingChildren
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
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
     * @Route("/doc/{id}/updateitems.{_format}", name="doctree_update_items")
     * @Method("POST")
     * @Security("is_granted('edit', lsDoc)")
     * @Template()
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     * @param string $_format
     *
     * @return array
     */
    public function updateItemsAction(Request $request, LsDoc $lsDoc, $_format = 'json'): array
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
     * @Route("/assocgroup/{id}/delete", name="lsdef_association_grouping_tree_delete")
     * @Method("POST")
     *
     * @param Request $request
     * @param LsDefAssociationGrouping $associationGrouping
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function deleteAssocGroupAction(Request $request, LsDefAssociationGrouping $associationGrouping): Response
    {
        $command = new DeleteAssociationGroupCommand($associationGrouping);
        $this->sendCommand($command);

        return new Response('OK', Response::HTTP_ACCEPTED);
    }

    /**
     * Create a response with a CFDocument
     *
     * @param int $id
     *
     * @return Response
     */
    protected function respondWithDocumentById(Request $request, int $id)
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
     * Create a response with a CFDocument
     *
     * @param string $identifier
     * @param LsDoc $lsDoc
     *
     * @return Response
     */
    protected function respondWithDocumentByIdentifier(Request $request, string $identifier, LsDoc $lsDoc)
    {
        $newDoc = $this->getDoctrine()->getRepository(LsDoc::class)->findOneBy(['identifier'=>$identifier]);
        if (null !== $newDoc) {
            return $this->exportAction($request, $newDoc);
        }

        // otherwise look in this doc's externalDocs
        // We could store, and check here, a global table of external documents that we could index by identifiers, instead of using document-specific associated docs. But it's not completely clear that would be an improvement.
        $externalDocs = $lsDoc->getExternalDocs();
        if (!empty($externalDocs[$identifier])) {
            // if we found it, load it, noting that we don't have to save a record of it in externalDocs (since it's already there)
            return $this->exportExternalDocument($externalDocs[$identifier]['url'], null);
        }

        // if not found in externalDocs, error
        return new Response('Document not found.', Response::HTTP_NOT_FOUND);
    }
}
