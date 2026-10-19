<?php

declare(strict_types=1);

namespace Tests\Unit\App\Serializer\Markdown;

use App\Entity\Framework\CfRubric;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDoc;
use App\Serializer\Markdown\GenericMarkdownNormalizer;

class GenericMarkdownNormalizerTest extends \Codeception\Test\Unit
{
    public function testSupportsDefTypesAndRubricsOnlyForMd(): void
    {
        $n = $this->makeNormalizer();
        self::assertTrue($n->supportsNormalization(new LsDefItemType(), 'md'));
        self::assertTrue($n->supportsNormalization(new CfRubric(), 'md'));
        self::assertFalse($n->supportsNormalization(new LsDefItemType(), 'json'));
        self::assertFalse($n->supportsNormalization(new LsDoc(), 'md'));
    }

    public function testNormalizesItemTypeWithFieldTable(): void
    {
        $type = new LsDefItemType();
        $type->setTitle('Benchmark');
        $type->setDescription('A test benchmark');
        $type->setCode('BM');
        $type->setHierarchyCode('1');

        $model = $this->makeNormalizer()->normalize($type, 'md');

        self::assertSame('ItemType', $model['front_matter']['objectType']);
        self::assertStringContainsString('# Benchmark', $model['body']);
        self::assertStringContainsString('A test benchmark', $model['body']);
    }

    private function makeNormalizer(): GenericMarkdownNormalizer
    {
        $router = $this->createMock(\Symfony\Component\Routing\RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []) => 'https://localhost/uri/'.($params['uri'] ?? $params['id'] ?? '')
        );
        $uriGenerator = new \App\Service\UriGenerator($router);
        $authChecker = $this->createMock(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);
        $objectHelper = $this->createMock(\App\Service\IdentifiableObjectHelper::class);
        $objectHelper->method('findObjectByIdentifier')->willReturn(null);
        $collector = new \App\Service\Markdown\AssociationRowsCollector($authChecker, new \App\Service\Api1Uris($router, $uriGenerator), $uriGenerator, $objectHelper);

        return new GenericMarkdownNormalizer(
            $uriGenerator,
            $collector,
            new \App\Service\Markdown\HtmlToMarkdownConverter(),
        );
    }
}
