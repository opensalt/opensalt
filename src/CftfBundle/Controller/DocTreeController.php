<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Form\Type\LsDocListType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
     * @Route("/doc/{id}.{_format}", name="doc_tree_view", defaults={"_format"="html", "lsItemId"=null})
     * @Route("/doc/{id}/{assocGroup}.{_format}", name="doc_tree_view", defaults={"_format"="html", "lsItemId"=null})
     * @Method({"GET"})
     * @Template()
     */
    public function viewAction(LsDoc $lsDoc, $_format = 'html', $lsItemId = null, $assocGroup = null)
    {

        // get form field for selecting a document (for tree2)
        $form = $this->createForm(LsDocListType::class, null, ['ajax' => false]);

        $em = $this->getDoctrine()->getManager();
        // TODO: get only association groupings tied to this document...
        $lsDefAssociationGroupings = $em->getRepository('CftfBundle:LsDefAssociationGrouping')->findAll();
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
            'lsItemId' => $lsItemId,
            'assocGroup' => $assocGroup,
            'docList' => $form->createView(),
            'assocGroups' => $lsDefAssociationGroupings,
            'lsDocs' => $lsDocs
        ];
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
        return $this->forward('CftfBundle:DocTree:view', ['lsDoc' => $lsItem->getLsDoc(), 'html', 'lsItemId' => $lsItem->getid(), 'assocGroup' => $assocGroup]);
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
        $lsDocId = $lsItem->getLsDoc()->getId();

        $em = $this->getDoctrine()->getManager();

        if ($includingChildren) {
            $em->getRepository(LsItem::class)->removeItemAndChildren($lsItem);
            $em->flush();
        } else {
            $em->getRepository(LsItem::class)->removeItem($lsItem);
            $em->flush();
        }

        if ($ajax) {
            return new Response($this->generateUrl('doc_tree_view', ['id' => $lsDocId]), Response::HTTP_ACCEPTED);
        } else {
            return $this->redirectToRoute('doc_tree_view', ['id' => $lsDocId]);
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
            // if addCopyToTitle is set, add "Copy of " to fullStatement
            if (array_key_exists('addCopyToTitle', $updates)) {
                $title = 'Copy of '.$lsItem->getFullStatement();
                $lsItem->setFullStatement($title);
            }

            $em->persist($lsItem);
            // flush here to generate ID for new lsItem
            $em->flush();

            // we will add the "CHILD_OF" relationship, as well as sequenceNumber, below
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
}
