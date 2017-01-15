<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Editor Tree controller.
 *
 * @Route("/cftree")
 */
class DocTreeController extends Controller
{
    /**
     * @Route("/lsdoc/{id}.{_format}", name="doc_tree_view", defaults={"_format"="html", "lsItemId"=null})
     * @Method({"GET"})
     * @Template()
     */
    public function viewAction(LsDoc $lsDoc, $_format = 'html', $lsItemId = null)
    {
        return [
            'lsDoc' => $lsDoc,
            'lsItemId' => $lsItemId
        ];
    }

    /**
     * @Route("/lsdoc/{lsDoc1_id}/{lsDoc2_id}.{_format}", name="doc_tree_view2", defaults={"_format"="html"})
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
     * @Route("/lsitem/{id}.{_format}", name="doc_tree_item_view", defaults={"_format"="html"})
     * @Method({"GET"})
     * @Template()
     */
    public function viewItemAction(LsItem $lsItem, $_format = 'html')
    {
        return $this->forward('CftfBundle:DocTree:View', ['lsDoc' => $lsItem->getLsDoc(), 'html', 'lsItemId' => $lsItem->getid()]);
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
    public function renderDocumentAction(LsDoc $lsDoc, $_format = 'html', $documentActive = "false")
    {
        $repo = $this->getDoctrine()->getRepository('CftfBundle:LsDoc');

        $items = $repo->findAllChildrenArray($lsDoc);
        $haveParents = $repo->findAllItemsWithParentsArray($lsDoc);
        $topChildren = $repo->findTopChildrenIds($lsDoc);

        $orphaned = $items;
        /* This list is now found in the $haveParents list
        foreach ($lsDoc->getTopLsItemIds() as $id) {
            // Not an orphan
            if (!empty($orphaned[$id])) {
                unset($orphaned[$id]);
            }
        }
        */
        foreach ($haveParents as $child) {
            // Not an orphan
            $id = $child['id'];
            if (!empty($orphaned[$id])) {
                unset($orphaned[$id]);
            }
        }

        return [
            'topItemIds'=>$topChildren,
            'lsDoc'=>$lsDoc,
            'items'=>$items,
            'orphaned' => $orphaned,
            'documentActive' => $documentActive
        ];
    }

    /**
     * @Route("/itemDetails/{id}", name="doc_tree_item_details")
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
}
