<?php

declare(strict_types=1);

namespace App\Controller\Editor;

use App\Security\Feature;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EditorShellController extends AbstractController
{
    public function __construct(
        private readonly FeatureManager $featureManager,
    ) {
    }

    #[Route(path: '/editor', name: 'editor_shell', methods: ['GET'])]
    #[Route(path: '/editor/{path}', name: 'editor_shell_path', requirements: ['path' => '.+'], methods: ['GET'])]
    public function index(): Response
    {
        $isDev = $this->getParameter('kernel.debug');
        $entry = 'src/main.js';

        return $this->render('editor/shell.html.twig', [
            'editorDevMode' => $isDev,
            'editorDevClient' => '/editor/@vite/client',
            'editorDevEntry' => '/editor/'.$entry,
            'editorProdScripts' => $this->buildProdScripts($entry),
            'editorProdStyles' => $this->buildProdStyles($entry),
            'editorFeatures' => [
                'comments' => $this->featureManager->isEnabled(Feature::COMMENTS),
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function buildProdScripts(string $entry): array
    {
        $manifest = $this->readManifest();
        $entryData = $manifest[$entry] ?? null;
        if (!\is_array($entryData) || !isset($entryData['file']) || !\is_string($entryData['file'])) {
            return [];
        }

        $scripts = ['/editor/'.$entryData['file']];
        foreach ($this->collectImports($manifest, $entryData) as $imported) {
            if (isset($imported['file']) && \is_string($imported['file'])) {
                $scripts[] = '/editor/'.$imported['file'];
            }
        }

        return array_values(array_unique($scripts));
    }

    /**
     * @return list<string>
     */
    private function buildProdStyles(string $entry): array
    {
        $manifest = $this->readManifest();
        $entryData = $manifest[$entry] ?? null;
        if (!\is_array($entryData)) {
            return [];
        }

        $styles = [];
        if (isset($entryData['css']) && \is_array($entryData['css'])) {
            foreach ($entryData['css'] as $cssFile) {
                if (\is_string($cssFile)) {
                    $styles[] = '/editor/'.$cssFile;
                }
            }
        }

        foreach ($this->collectImports($manifest, $entryData) as $imported) {
            if (isset($imported['css']) && \is_array($imported['css'])) {
                foreach ($imported['css'] as $cssFile) {
                    if (\is_string($cssFile)) {
                        $styles[] = '/editor/'.$cssFile;
                    }
                }
            }
        }

        return array_values(array_unique($styles));
    }

    /**
     * @param array<string, mixed>              $manifest
     * @param array<string, mixed>              $entryData
     * @param array<string, array<string, mixed>> $imports
     *
     * @return array<string, array<string, mixed>>
     */
    private function collectImports(array $manifest, array $entryData, array &$imports = []): array
    {
        if (!isset($entryData['imports']) || !\is_array($entryData['imports'])) {
            return $imports;
        }

        foreach ($entryData['imports'] as $importKey) {
            if (!\is_string($importKey) || isset($imports[$importKey])) {
                continue;
            }
            $importData = $manifest[$importKey] ?? null;
            if (!\is_array($importData)) {
                continue;
            }
            $imports[$importKey] = $importData;
            $this->collectImports($manifest, $importData, $imports);
        }

        return $imports;
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(): array
    {
        $manifestPath = (string) $this->getParameter('kernel.project_dir').'/public/editor/.vite/manifest.json';
        if (!is_file($manifestPath)) {
            return [];
        }

        $manifestData = file_get_contents($manifestPath);
        if (false === $manifestData) {
            return [];
        }

        try {
            $decoded = json_decode($manifestData, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return \is_array($decoded) ? $decoded : [];
    }
}
