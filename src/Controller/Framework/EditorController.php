<?php

namespace App\Controller\Framework;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Util\Compare;
use Symfony\Component\HttpFoundation\Response;

/**
 * Editor controller.
 *
 * @Route("/cf")
 */
class EditorController extends AbstractController
{
    /**
     * @Route("/", defaults={"_format"="html"}, name="editor_index")
     * @Method("GET")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(): Response
    {
        return $this->forward('App\Controller\Framework\LsDoc:index', []);
    }

    /**
     * @Route("/doc/{id}.{_format}", defaults={"_format" = "html"}, name="editor_lsdoc")
     * @Method("GET")
     * @Template()
     *
     * @param \App\Entity\Framework\LsDoc $lsDoc
     * @param string $_format
     *
     * @return array|Response
     */
    public function viewDocAction(LsDoc $lsDoc, $_format)
    {
        if ('json' === $_format) {
            return $this->forward('App\Controller\Framework\LsDoc:export', ['lsDoc' => $lsDoc]);
        }

        return ['lsDoc'=>$lsDoc];
    }

    /**
     * @Route("/item/{id}.{_format}", defaults={"_format" = "html"}, name="editor_lsitem")
     * @Method("GET")
     * @Template()
     *
     * @param \App\Entity\Framework\LsItem $lsItem
     * @param string $_format
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function viewItemAction(LsItem $lsItem, $_format)
    {
        if ('json' === $_format) {
            return $this->forward('App\Controller\Framework\LsItem:export', ['lsItem' => $lsItem]);
        }

        return ['lsItem'=>$lsItem];
    }

    /**
     * @Route("/render/{id}.{_format}", defaults={"highlight"=null, "_format"="html"}, name="editor_render_document_only")
     * @Route("/render/{id}/{highlight}.{_format}", defaults={"highlight"=null, "_format"="html"}, name="editor_render")
     * @Method("GET")
     * @Template()
     *
     * @param \App\Entity\Framework\LsDoc $lsDoc
     * @param int $highlight
     * @param string $_format
     *
     * @return array
     */
    public function renderDocumentAction(LsDoc $lsDoc, $highlight = null, $_format = 'html'): array
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
        Compare::sortArrayByFields($orphaned, ['rank', 'listEnumInSource', 'humanCodingScheme']);

        return [
            'topItemIds'=>$topChildren,
            'lsDoc'=>$lsDoc,
            'items'=>$items,
            'highlight' => $highlight,
            'orphaned' => $orphaned,
        ];
    }
}
