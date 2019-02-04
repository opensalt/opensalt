<?php

namespace App\Controller\Framework;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Util\Compare;

/**
 * Editor controller.
 *
 * @Route("/cf")
 */
class EditorController extends AbstractController
{
    /**
     * @Route("/doc/{id}.{_format}", methods={"GET"}, defaults={"_format" = "html"}, name="editor_lsdoc")
     * @Template()
     */
    public function viewDocAction(LsDoc $lsDoc, string $_format = 'html')
    {
        if ('json' === $_format) {
            return $this->forward('App\Controller\Framework\LsDocController:exportAction', ['lsDoc' => $lsDoc]);
        }

        return ['lsDoc' => $lsDoc];
    }

    /**
     * @Route("/item/{id}.{_format}", methods={"GET"}, defaults={"_format" = "html"}, name="editor_lsitem")
     * @Template()
     */
    public function viewItemAction(LsItem $lsItem, string $_format = 'html')
    {
        if ('json' === $_format) {
            return $this->forward('App\Controller\Framework\LsItemController:exportAction', ['lsItem' => $lsItem]);
        }

        return ['lsItem' => $lsItem];
    }

    /**
     * @Route("/render/{id}.{_format}", methods={"GET"}, defaults={"highlight"=null, "_format"="html"}, name="editor_render_document_only")
     * @Route("/render/{id}/{highlight}.{_format}", methods={"GET"}, defaults={"highlight"=null, "_format"="html"}, name="editor_render")
     * @Template()
     */
    public function renderDocumentAction(LsDoc $lsDoc, ?int $highlight = null, $_format = 'html'): array
    {
        $repo = $this->getDoctrine()->getRepository(LsDoc::class);

        $items = $repo->findAllChildrenArray($lsDoc);
        $haveParents = $repo->findAllItemsWithParentsArray($lsDoc);
        $topChildren = $repo->findTopChildrenIds($lsDoc);

        $orphaned = $items;
        foreach ($haveParents as $child) {
            // Not an orphan
            $id = $child['id'];
            if (!empty($orphaned[$id])) {
                unset($orphaned[$id]);
            }
        }
        Compare::sortArrayByFields($orphaned, ['listEnumInSource', 'humanCodingScheme']);

        return [
            'topItemIds' => $topChildren,
            'lsDoc' => $lsDoc,
            'items' => $items,
            'highlight' => $highlight,
            'orphaned' => $orphaned,
        ];
    }
}
