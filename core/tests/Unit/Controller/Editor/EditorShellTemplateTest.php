<?php

declare(strict_types=1);

namespace Tests\Unit\Controller\Editor;

use Codeception\Attribute\Skip;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

#[Skip('Skip temporarily due to not being able to get container')]
final class EditorShellTemplateTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    public function testRendersCommentsFeatureEnabledFlag(): void
    {
        $html = $this->renderShell(['comments' => true]);

        self::assertStringContainsString('"comments":true', $html);
        self::assertStringContainsString('window.openSaltEditor', $html);
    }

    public function testRendersCommentsFeatureDisabledFlag(): void
    {
        $html = $this->renderShell(['comments' => false]);

        self::assertStringContainsString('"comments":false', $html);
        self::assertStringContainsString('window.openSaltEditor', $html);
    }

    /**
     * @param array<string, bool> $features
     */
    private function renderShell(array $features): string
    {
        self::bootKernel();

        $container = static::getContainer();
        $requestStack = $container->get(RequestStack::class);
        $requestStack->push(Request::create('/editor'));

        try {
            return $container->get(Environment::class)->render('editor/shell.html.twig', [
                'editorDevMode' => false,
                'editorDevClient' => '/editor/@vite/client',
                'editorDevEntry' => '/editor/src/main.js',
                'editorProdScripts' => [],
                'editorProdStyles' => [],
                'editorFeatures' => $features,
            ]);
        } finally {
            $requestStack->pop();
            self::ensureKernelShutdown();
        }
    }
}
