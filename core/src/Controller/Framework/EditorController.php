<?php

namespace App\Controller\Framework;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Util\Compare;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/cf')]
class EditorController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    #[Route(path: '/doc/{id}.{_format}', name: 'editor_lsdoc', defaults: ['_format' => 'html'], methods: ['GET'])]
    public function viewDoc(LsDoc $lsDoc, string $_format = 'html'): Response
    {
        if ('json' === $_format) {
            return $this->forward(LsDocController::class.'::export', ['lsDoc' => $lsDoc]);
        }

        return $this->render('framework/editor/view_doc.html.twig', ['lsDoc' => $lsDoc]);
    }

    #[Route(path: '/item/{id}.{_format}', name: 'editor_lsitem', defaults: ['_format' => 'html'], methods: ['GET'])]
    public function viewItem(LsItem $lsItem, string $_format = 'html'): Response
    {
        if ('json' === $_format) {
            return $this->forward(LsItemController::class.'::export', ['lsItem' => $lsItem]);
        }

        return $this->render('framework/editor/view_item.html.twig', ['lsItem' => $lsItem]);
    }

    #[Route(path: '/render/{id}.{_format}', name: 'editor_render_document_only', defaults: ['highlight' => null, '_format' => 'html'], methods: ['GET'])]
    #[Route(path: '/render/{id}/{highlight}.{_format}', name: 'editor_render', defaults: ['highlight' => null, '_format' => 'html'], methods: ['GET'])]
    public function renderDocument(LsDoc $lsDoc, ?int $highlight = null, string $_format = 'html'): Response
    {
        $repo = $this->managerRegistry->getRepository(LsDoc::class);

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

        return $this->render('framework/editor/render_document.'.$_format.'.twig', [
            'topItemIds' => $topChildren,
            'lsDoc' => $lsDoc,
            'items' => $items,
            'highlight' => $highlight,
            'orphaned' => $orphaned,
        ]);
    }
}
