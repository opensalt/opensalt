<?php

declare(strict_types=1);

namespace Tests\Unit\App\Serializer\Markdown;

use App\Entity\Framework\LsDoc;
use App\Serializer\Markdown\LsDocMarkdownNormalizer;

class LsDocMarkdownNormalizerTest extends \Codeception\Test\Unit
{
    public function testNormalizesDocumentWithAboutAndPackageLink(): void
    {
        $doc = new LsDoc();
        $doc->setIdentifier('22222222-2222-4222-8222-222222222222');
        $doc->setTitle('Health Science Framework');
        $doc->setCreator('Test Creator');
        $doc->setDescription('<p>A framework about health</p>');
        $doc->setAdoptionStatus('Draft');
        $doc->setChangedAt(new \DateTimeImmutable('2026-10-01T00:00:00+00:00'));

        $router = $this->createMock(\Symfony\Component\Routing\RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []) => 'https://localhost/uri/'.($params['uri'] ?? $params['id'] ?? '')
        );

        $docRepository = $this->createMock(\App\Repository\Framework\LsDocRepository::class);
        $docRepository->method('findItemTocRows')->willReturn([]);
        $docRepository->method('findItemParentMap')->willReturn([]);

        $n = new LsDocMarkdownNormalizer(
            new \App\Service\UriGenerator($router),
            new \App\Service\Markdown\HtmlToMarkdownConverter(),
            $docRepository,
        );

        self::assertTrue($n->supportsNormalization($doc, 'md'));
        self::assertFalse($n->supportsNormalization($doc, 'md', ['markdown-package' => true]));

        $model = $n->normalize($doc, 'md');
        self::assertSame('Document', $model['front_matter']['objectType']);
        self::assertSame('Health Science Framework', $model['front_matter']['title']);
        self::assertSame('Test Creator', $model['front_matter']['creator']);
        self::assertSame('Draft', $model['front_matter']['adoptionStatus']);
        self::assertStringContainsString('# Health Science Framework', $model['body']);
        self::assertStringContainsString('## About this framework', $model['body']);
        self::assertStringContainsString('A framework about health', $model['body']);
        self::assertStringContainsString('## Full package', $model['body']);
    }
}
