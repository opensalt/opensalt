<?php

declare(strict_types=1);

namespace App\Controller\Framework;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class LegacyEditorRedirectController extends AbstractController
{
    #[Route(path: '/cftree/doc/{id}', name: 'cftree_doc_redirect', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function redirectToNewEditor(#[MapEntity(id: 'id')] LsDoc $lsDoc): RedirectResponse
    {
        return $this->redirectToRoute('editor_shell_path', ['path' => $lsDoc->getIdentifier()]);
    }

    #[Route(path: '/old-editor/{identifier}/{path}', name: 'old_editor_redirect', defaults: ['path' => null], requirements: ['identifier' => '[a-fA-F0-9]{8}-([a-fA-F0-9]{4}-){3}[a-fA-F0-9]{12}', 'path' => '.*'], methods: ['GET'])]
    public function redirectToLegacyEditor(#[MapEntity(expr: 'repository.findOneByIdentifier(identifier)')] LsDoc $lsDoc, ?string $path = null): RedirectResponse
    {
        return $this->redirectToRoute('doc_tree_view', ['slug' => $lsDoc->getId()]);
    }

    #[Route(path: '/cftree/item/{id}', name: 'cftree_item_redirect', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function redirectItemToNewEditor(#[MapEntity(id: 'id')] LsItem $lsItem): RedirectResponse
    {
        return $this->redirectToRoute('editor_shell_path', [
            'path' => $lsItem->getLsDoc()->getIdentifier().'/'.$lsItem->getIdentifier(),
        ]);
    }
}
