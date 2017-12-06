<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Form\Type\LsDocListType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Util\Compare;

/**
 * Editor Tree controller.
 *
 * @Route("/cftree")
 */
class DocTreeController extends Controller
{
    /**
     * @Route("/doc/{slug}.{_format}", name="doc_tree_view", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/doc/{slug}/av.{_format}", name="doc_tree_view_av", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/doc/{slug}/{assocGroup}.{_format}", name="doc_tree_view_ag", defaults={"_format"="html", "lsItemId"=null})
     * @ParamConverter("lsDoc", class="CftfBundle:LsDoc", options={
     *     "repository_method" = "findOneBySlug",
     *     "mapping": {"slug": "slug"},
     *     "map_method_signature" = true
     * })
     * @Method({"GET"})
     * @Template()
     */
    public function viewAction(LsDoc $lsDoc, $_format = 'html', $lsItemId = null, $assocGroup = null)
    {
        // get form field for selecting a document (for tree2)
        $form = $this->createForm(LsDocListType::class, null, ['ajax' => false]);

        $em = $this->getDoctrine()->getManager();

        // Get all association groups (for all documents);
        // we need groups for other documents if/when we show a document on the right side
        $lsDefAssociationGroupings = $em->getRepository('CftfBundle:LsDefAssociationGrouping')->findAll();

        $assocTypes = [];
        $inverseAssocTypes = [];
        foreach (LsAssociation::allTypes() as $type) {
            $assocTypes[] = $type;
            $inverseAssocTypes[] = LsAssociation::inverseName($type);
        }

        // get list of all documents
        $resultlsDocs = $em->getRepository('CftfBundle:LsDoc')->findBy([], ['creator'=>'ASC', 'title'=>'ASC', 'adoptionStatus'=>'ASC']);
        $lsDocs = [];
        $authChecker = $this->get('security.authorization_checker');
        foreach ($resultlsDocs as $doc) {
            if ($authChecker->isGranted('view', $doc)) {
                $lsDocs[] = $doc;
            }
        }

        return [
            'lsDoc' => $lsDoc,
            'lsDocId' => $lsDoc->getId(),
            'lsDocTitle' => $lsDoc->getTitle(),

            'editorRights' => $authChecker->isGranted('edit', $lsDoc),
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
            'lsDocs' => $lsDocs
        ];
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
    public function exportAction(LsDoc $lsDoc)
    {
        $items = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findItemsForExportDoc($lsDoc);
        $associations = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findAssociationsForExportDoc($lsDoc);
        $assocGroups = $this->getDoctrine()->getRepository('CftfBundle:LsDefAssociationGrouping')->findBy(['lsDoc'=>$lsDoc]);
        $associatedDocs = array_merge(
            $lsDoc->getExternalDocs(),
            $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findAssociatedDocs($lsDoc)
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
        $response = new Response($this->renderView('CftfBundle:DocTree:export.json.twig', $arr));
        $response->headers->set('Content-Type', 'text/json');
        $response->headers->set('Pragma', 'no-cache');

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
            return $this->respondWithDocumentById($id);
        }

        // or an identifier...
        if (null !== $lsDoc && $identifier = $request->query->get('identifier')) {
            // first see if it's referencing a document on this OpenSALT instantiation
            return $this->respondWithDocumentByIdentifier($identifier, $lsDoc);
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
                    if (!empty($externalDocs[$identifier])) {
                        $autoLoad = $externalDocs[$identifier]['autoLoad'];
                    } else {
                        // if it's a newly-associated doc, assume here that it does not need to be "autoloaded"; that will be changed if/when we add an association with an item in the doc
                        $autoLoad = 'false';
                    }

                    // if this is a new doc or anything has changed, save it
                    if (empty($externalDocs[$identifier]) || $externalDocs[$identifier]['autoLoad'] != $autoLoad || $externalDocs[$identifier]['url'] != $url || $externalDocs[$identifier]['title'] != $title) {
                        $lsDoc->addExternalDoc($identifier, $autoLoad, $url, $title);
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($lsDoc);
                        $em->flush();
                    }
                }
            }

            // now return the file
            $response = new Response($s);
            $response->headers->set('Content-Type', 'text/json');
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
        $repo = $this->getDoctrine()->getRepository('CftfBundle:LsDoc');

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
     */
    public function deleteAction(Request $request, LsItem $lsItem, $includingChildren = 0)
    {
        $ajax = false;
        if ($request->isXmlHttpRequest()) {
            $ajax = true;
        }
        $lsDocSlug = $lsItem->getLsDoc()->getSlug();

        $em = $this->getDoctrine()->getManager();

        if ($includingChildren) {
            $em->getRepository(LsItem::class)->removeItemAndChildren($lsItem);
            $em->flush();
        } else {
            $em->getRepository(LsItem::class)->removeItem($lsItem);
            $em->flush();
        }

        if ($ajax) {
            return new Response($this->generateUrl('doc_tree_view', ['slug' => $lsDocSlug]), Response::HTTP_ACCEPTED);
        } else {
            return $this->redirectToRoute('doc_tree_view', ['slug' => $lsDocSlug]);
        }
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
     *
     * @return array
     */
    public function updateItemsAction(Request $request, LsDoc $lsDoc, $_format = 'json')
    {
        $rv = [];

        $em = $this->getDoctrine()->getManager();
        $assocGroupRepo = $em->getRepository(LsDefAssociationGrouping::class);

        $lsItems = $request->request->get('lsItems');
        foreach ($lsItems as $lsItemId => $updates) {
            $rv[$lsItemId] = [
                'originalKey' => $updates['originalKey'],
            ];

            // set assocGroup if supplied; pass this in when necessary below
            $assocGroup = null;
            if (array_key_exists('assocGroup', $updates)) {
                $assocGroup = $assocGroupRepo->find($updates['assocGroup']);
            }

            $lsItem = $this->getItemForUpdate($lsDoc, $updates, $lsItemId, $assocGroup);

            // return the id and fullStatement of the item, whether it's new or it already existed
            $rv[$lsItemId]['lsItemId'] = $lsItem->getId();
            $rv[$lsItemId]['lsItemIdentifier'] = $lsItem->getIdentifier();
            $rv[$lsItemId]['fullStatement'] = $lsItem->getFullStatement();

            if (array_key_exists('deleteChildOf', $updates)) {
                $this->deleteChildAssociations($lsItem, $updates, $lsItemId, $rv);
            } elseif (array_key_exists('updateChildOf', $updates)) {
                $this->updateChildOfAssociations($lsItem, $updates, $lsItemId, $rv);
            }

            // create new childOf association if specified
            if (array_key_exists('newChildOf', $updates)) {
                $this->addChildOfAssociations($lsItem, $updates, $lsItemId, $rv, $assocGroup);
            }
        }

        // send new lsItem updatedAt??

        $em->flush();

        // get ids for new associations
        foreach ($rv as $lsItemId => $val) {
            if (!empty($rv[$lsItemId]['association'])) {
                $rv[$lsItemId]['assocId'] = $rv[$lsItemId]['association']->getId();
                unset($rv[$lsItemId]['association']);
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
     * @param LsDefAssociationGrouping $lsDefAssociationGrouping
     *
     * @return string
     */
    public function deleteAssocGroupAction(Request $request, LsDefAssociationGrouping $lsDefAssociationGrouping)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($lsDefAssociationGrouping);
        $em->flush();

        return new Response('OK', Response::HTTP_ACCEPTED);
    }

    /**
     * Get the item to update, either the original or a copy based on the update array
     *
     * @param LsDoc $lsDoc
     * @param array $updates
     * @param int $lsItemId
     * @param LsDefAssociationGrouping|null $assocGroup
     *
     * @return LsItem
     */
    protected function getItemForUpdate(LsDoc $lsDoc, array $updates, $lsItemId, ?LsDefAssociationGrouping $assocGroup = null): LsItem
    {
        $em = $this->getDoctrine()->getManager();
        $lsItemRepo = $em->getRepository(LsItem::class);

        // copy item if copyFromId is specified
        if (array_key_exists('copyFromId', $updates)) {
            $originalItem = $lsItemRepo->find($updates['copyFromId']);

            $lsItem = $originalItem->copyToLsDoc($lsDoc, $assocGroup);
            // if addCopyToTitle is set, add "Copy of " to fullStatement and abbreviatedStatement
            if (array_key_exists('addCopyToTitle', $updates)) {
                $title = 'Copy of '.$lsItem->getFullStatement();
                $lsItem->setFullStatement($title);

                $astmt = $lsItem->getAbbreviatedStatement();
                if (!empty($astmt)) {
                    $astmt = 'Copy of '.$astmt;
                    $lsItem->setAbbreviatedStatement($astmt);
                }
            }

            $em->persist($lsItem);
            // flush here to generate ID for new lsItem
            $em->flush();

        } else {
            // else get lsItem from the repository
            $lsItem = $lsItemRepo->find($lsItemId);
        }

        return $lsItem;
    }

    /**
     * Remove the appropriate childOf associations for the item based on the update array
     *
     * @param LsItem $lsItem
     * @param array $updates
     * @param int $lsItemId
     * @param array $rv
     */
    protected function deleteChildAssociations(LsItem $lsItem, array $updates, $lsItemId, array &$rv): void
    {
        $em = $this->getDoctrine()->getManager();
        $assocRepo = $em->getRepository(LsAssociation::class);

        // delete childOf association if specified
        if ($updates['deleteChildOf']['assocId'] !== 'all') {
            $assocRepo->removeAssociation($assocRepo->find($updates['deleteChildOf']['assocId']));
            $lsItem->setUpdatedAt(new \DateTime());
            $rv[$lsItemId]['deleteChildOf'] = $updates['deleteChildOf']['assocId'];
        } else {
            // if we got "all" for the assocId, it means that we're updating a new item for which the client didn't know an assocId.
            // so in this case, it's OK to just delete any existing childof association and create a new one below
            $assocRepo->removeAllAssociationsOfType($lsItem, LsAssociation::CHILD_OF);
        }
    }

    /**
     * Update the childOf associations based on the update array
     *
     * @param LsItem $lsItem
     * @param array $updates
     * @param int $lsItemId
     * @param array $rv
     */
    protected function updateChildOfAssociations(LsItem $lsItem, array $updates, $lsItemId, array &$rv): void
    {
        $em = $this->getDoctrine()->getManager();
        $assocRepo = $em->getRepository(LsAssociation::class);

        // update childOf association if specified
        $assoc = $assocRepo->find($updates['updateChildOf']['assocId']);
        if (!empty($assoc)) {
            // as of now the only thing we update is sequenceNumber
            if (array_key_exists('sequenceNumber', $updates['updateChildOf'])) {
                $assoc->setSequenceNumber($updates['updateChildOf']['sequenceNumber']*1);
            }
            $rv[$lsItemId]['association'] = $assoc;
            $rv[$lsItemId]['sequenceNumber'] = $updates['updateChildOf']['sequenceNumber'];
        }
        $lsItem->setUpdatedAt(new \DateTime());
    }

    /**
     * Add new childOf associations based on the update array
     *
     * @param LsItem $lsItem
     * @param array $updates
     * @param int $lsItemId
     * @param array $rv
     */
    protected function addChildOfAssociations(LsItem $lsItem, array $updates, $lsItemId, array &$rv, ?LsDefAssociationGrouping $assocGroup = null): void
    {
        $em = $this->getDoctrine()->getManager();

        // parent could be a doc or item
        if ($updates['newChildOf']['parentType'] === 'item') {
            $lsItemRepo = $em->getRepository(LsItem::class);
            $parentItem = $lsItemRepo->find($updates['newChildOf']['parentId']);
        } else {
            $docRepo = $em->getRepository(LsDoc::class);
            $parentItem = $docRepo->find($updates['newChildOf']['parentId']);
        }
        $rv[$lsItemId]['association'] = $lsItem->addParent($parentItem, $updates['newChildOf']['sequenceNumber'], $assocGroup);
        $lsItem->setUpdatedAt(new \DateTime());

        $rv[$lsItemId]['sequenceNumber'] = $updates['newChildOf']['sequenceNumber'];
    }

    /**
     * Create a response with a CFDocument
     *
     * @param int $id
     *
     * @return Response
     */
    protected function respondWithDocumentById(int $id)
    {
        // in this case it has to be a document on this OpenSALT instantiation
        $newDoc = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->find($id);
        if (empty($newDoc)) {
            // if document not found, error
            return new Response('Document not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->exportAction($newDoc);
    }

    /**
     * Create a response with a CFDocument
     *
     * @param string $identifier
     * @param LsDoc $lsDoc
     *
     * @return Response
     */
    protected function respondWithDocumentByIdentifier(string $identifier, LsDoc $lsDoc)
    {
        $newDoc = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findOneBy(['identifier'=>$identifier]);
        if (null !== $newDoc) {
            return $this->exportAction($newDoc);
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
