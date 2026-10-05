<?php

declare(strict_types=1);

namespace App\Controller\Framework;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait RedirectsToFrameworkEditorTrait
{
    private function redirectToFrameworkEditor(LsDoc $lsDoc, ?string $editorPathSuffix = null): RedirectResponse
    {
        $path = $lsDoc->getIdentifier();
        if (null !== $editorPathSuffix && '' !== $editorPathSuffix) {
            $path .= '/'.$editorPathSuffix;
        }

        return $this->redirectToRoute('editor_shell_path', ['path' => $path], Response::HTTP_MOVED_PERMANENTLY);
    }

    private function redirectToFrameworkItemEditor(LsItem $lsItem): RedirectResponse
    {
        return $this->redirectToRoute('editor_shell_path', [
            'path' => $this->frameworkEditorPathForItem($lsItem),
        ], Response::HTTP_MOVED_PERMANENTLY);
    }

    private function frameworkEditorPathForDocument(LsDoc $lsDoc): string
    {
        return $lsDoc->getIdentifier();
    }

    private function frameworkEditorPathForItem(LsItem $lsItem): string
    {
        return $lsItem->getLsDoc()->getIdentifier().'/'.$lsItem->getIdentifier();
    }

    private function frameworkEditorUrlForDocument(
        LsDoc $lsDoc,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        return $this->generateUrl('editor_shell_path', [
            'path' => $this->frameworkEditorPathForDocument($lsDoc),
        ], $referenceType);
    }

    private function frameworkEditorUrlForItem(
        LsItem $lsItem,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        return $this->generateUrl('editor_shell_path', [
            'path' => $this->frameworkEditorPathForItem($lsItem),
        ], $referenceType);
    }
}
