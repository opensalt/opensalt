<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
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
     * @Route("/doc/{id}.{_format}", name="doc_tree_view", defaults={"_format"="html", "lsItemId"=null})
     * @Method({"GET"})
     * @Template()
     */
    public function viewAction(LsDoc $lsDoc, $_format = 'html', $lsItemId = null)
    {

        // get form field for selecting a document (for tree2)
        $form = $this->createForm(LsDocListType::class, null, ['ajax' => false]);

        return [
            'lsDoc' => $lsDoc,
            'lsItemId' => $lsItemId,
            'docList' => $form->createView()
        ];
    }

    /**
     * @Route("/doc/{lsDoc1_id}/{lsDoc2_id}.{_format}", name="doc_tree_view2", defaults={"_format"="html"})
     * @ParamConverter("lsDoc1", class="CftfBundle:LsDoc", options={"id"="lsDoc1_id"})
     * @ParamConverter("lsDoc2", class="CftfBundle:LsDoc", options={"id"="lsDoc2_id"})
     * @Method({"GET"})
     * @Template()
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc1
     * @param \CftfBundle\Entity\LsDoc $lsDoc2
     *
     * @return array
     */
    public function view2Action(LsDoc $lsDoc1, LsDoc $lsDoc2, $_format = 'html')
    {
        return [
            'lsDoc1' => $lsDoc1,
            'lsDoc2' => $lsDoc2,
        ];
    }

    /**
     * @Route("/item/{id}.{_format}", name="doc_tree_item_view", defaults={"_format"="html"})
     * @Method({"GET"})
     *
     * @param LsItem $lsItem
     * @param string $_format
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewItemAction(LsItem $lsItem, $_format = 'html')
    {
        return $this->forward('CftfBundle:DocTree:view', ['lsDoc' => $lsItem->getLsDoc(), 'html', 'lsItemId' => $lsItem->getid()]);
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
     * @Route("/item/{id}/details", name="doc_tree_item_details")
     * @Method("GET")
     * @Template()
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
        $lsDocId = $lsDoc->getId();

        // by default we'll return the url of the document
        // $returnUrl = new Response($this->generateUrl('doc_tree_view', ['id' => $lsDocId]), Response::HTTP_ACCEPTED);
        $rv = [];

        $em = $this->getDoctrine()->getManager();
        $lsItemRepo = $em->getRepository(LsItem::class);

        $lsItems = $request->request->get('lsItems');
        foreach ($lsItems as $lsItemId => $updates) {
            $copiedItem = false;

            // copy item if copyFromId is specified
            if (array_key_exists('copyFromId', $updates)) {
                $copiedItem = true;
                $originalItem = $lsItemRepo->find($updates['copyFromId']);

                // PW: code based on CopyToLsDocCommand
                $lsItem = $originalItem->copyToLsDoc($lsDoc);
                $em->persist($lsItem);
                // flush here to generate ID for new lsItem
                $em->flush();

                // if we create a new item, we'll return the url of the new item
                // $returnUrl = new Response($this->generateUrl('doc_tree_item_view', ['id' => $lsItem->getId()]), Response::HTTP_ACCEPTED);
                $rv[] = [
                    'copyFromId' => $updates['copyFromId'],
                    'lsItemId' => $lsItem->getId()
                ];

                // we will add the "CHILD_OF" relationship, as well as listEnumInSource, below

            // else get lsItem from the repository
            } else {
                $lsItem = $lsItemRepo->find($lsItemId);
            }

            // change listEnumInSource if listEnumInSource is specified
            if (array_key_exists('listEnumInSource', $updates)) {
                $lsItem->setListEnumInSource($updates['listEnumInSource']);
            }

            // set/change parent if parentId is specified
            if (array_key_exists('parentId', $updates)) {
                // parent could be a doc or item
                if ($updates['parentType'] === 'item') {
                    $parentItem = $lsItemRepo->find($updates['parentId']);
                } else {
                    $parentItem = $em->getRepository(LsDoc::class)->find($updates['parentId']);
                }
                // PW: code mostly copied from ChangeLsItemParentCommand
                $lsItem->setUpdatedAt(new \DateTime());
                // unless we copied the item, we need to remove previous CHILD_OF relationships.
                if (!$copiedItem) {
                    $em->getRepository(LsAssociation::class)->removeAllAssociationsOfType($lsItem, LsAssociation::CHILD_OF);
                    // if we do this for a copied item, we also remove the CHILD_OF relationships for the original item
                }
                $lsItem->addParent($parentItem);
            }

            // Note: this could be extended to allow for other updates if we wanted to do that...
        }

        $em->flush();

        if (sizeof($rv) == 0) {
            return ['topItems' => $rv];
        } else {
            // get doc items for return
            $items = $this->getDoctrine()->getRepository('CftfBundle:LsDoc')->findAllChildrenArray($lsDoc);
            return [
                'topItems' => $rv,
                'items' => $items
            ];
        }
    }
}
