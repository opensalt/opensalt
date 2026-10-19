<?php

declare(strict_types=1);

namespace Tests\Unit\App\Serializer\Markdown;

use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Serializer\Markdown\LsItemMarkdownNormalizer;
use App\Service\Api1Uris;
use App\Service\Markdown\AssociationRowsCollector;
use App\Service\Markdown\HtmlToMarkdownConverter;
use App\Service\Markdown\ItemRendererLocator;
use App\Service\Markdown\MarkdownBuilder;
use App\Service\Markdown\Renderer\DefaultItemRenderer;
use App\Service\UriGenerator;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Yaml\Yaml;

class LsItemMarkdownNormalizerTest extends \Codeception\Test\Unit
{
    public function testSupportsOnlyMdAndLsItem(): void
    {
        $n = $this->makeNormalizer();
        self::assertTrue($n->supportsNormalization(new LsItem(), 'md'));
        self::assertFalse($n->supportsNormalization(new LsItem(), 'json'));
        self::assertFalse($n->supportsNormalization(new LsDoc(), 'md'));
    }

    public function testNormalizesStandardItemModel(): void
    {
        $doc = new LsDoc();
        $doc->setTitle('Test framework');

        $itemType = $this->createMock(LsDefItemType::class);
        $itemType->method('getTitle')->willReturn('Benchmark');

        $item = new LsItem();
        $item->setIdentifier('11111111-1111-4111-8111-111111111111');
        $item->setLsDoc($doc);
        $item->setFullStatement('<p>Solve problems</p>');
        $item->setAbbreviatedStatement('Solve');
        $item->setHumanCodingScheme('MATH.1');
        $item->setItemType($itemType);
        $item->setChangedAt(new \DateTimeImmutable('2026-10-01T12:00:00+00:00'));

        $model = $this->makeNormalizer()->normalize($item, 'md');

        $fm = Yaml::parse(MarkdownBuilder::yaml($model['front_matter']));
        self::assertSame('Item', $fm['objectType']);
        self::assertSame('item', $fm['kind']);
        self::assertSame('Solve', $fm['title']);
        self::assertSame('MATH.1', $fm['humanCodingScheme']);
        self::assertSame('Benchmark', $fm['itemType']);
        self::assertSame('2026-10-01T12:00:00+00:00', $fm['lastChangeDateTime']);

        self::assertStringContainsString('# Solve (MATH.1)', $model['body']);
        self::assertStringContainsString('## Statement', $model['body']);
        self::assertStringContainsString('Solve problems', $model['body']);
    }

    public function testPackageModeAddsIdentityLineAndHeadingOverride(): void
    {
        $item = new LsItem();
        $item->setLsDoc(new LsDoc());
        $item->setIdentifier('11111111-1111-4111-8111-111111111111');
        $item->setAbbreviatedStatement('Child');
        $item->setChangedAt(new \DateTimeImmutable());

        $model = $this->makeNormalizer()->normalize($item, 'md', [
            'markdown-package' => true,
            'markdown-heading' => ['level' => 3, 'text' => '1.1 Child'],
        ]);

        self::assertStringContainsString('### 1.1 Child', $model['body']);
        self::assertStringContainsString('`Item`', $model['body']);
        self::assertStringContainsString('11111111-1111-4111-8111-111111111111', $model['body']);
    }

    public function testAncestorPathIncludedForSingleFetchOnly(): void
    {
        $doc = new LsDoc();
        $doc->setTitle('Test framework');

        $parent = new LsItem();
        $parent->setLsDoc($doc);
        $parent->setAbbreviatedStatement('Parent');

        $child = new LsItem();
        $child->setLsDoc($doc);
        $child->setAbbreviatedStatement('Child');

        $assoc = new \App\Entity\Framework\LsAssociation();
        $assoc->setLsDoc($doc);
        $assoc->setType(\App\Entity\Framework\LsAssociation::CHILD_OF);
        $assoc->setOrigin($child);
        $assoc->setDestination($parent);
        $child->addAssociation($assoc);

        $model = $this->makeNormalizer()->normalize($child, 'md');

        self::assertStringContainsString('## Ancestor Path', $model['body']);
        self::assertStringContainsString('1. [Test framework]', $model['body']);
        self::assertStringContainsString('2. [Parent]', $model['body']);
        self::assertStringContainsString("\n3. Child", $model['body']);

        $packageModel = $this->makeNormalizer()->normalize($child, 'md', [
            'markdown-package' => true,
            'markdown-heading' => ['level' => 3, 'text' => '1.1 Child'],
        ]);
        self::assertStringNotContainsString('Ancestor Path', $packageModel['body']);
    }

    public function testComplexExtensionsGoToBodyNotFrontMatter(): void
    {
        $item = new LsItem();
        $item->setLsDoc(new LsDoc());
        $item->setAbbreviatedStatement('Ext');
        $item->setChangedAt(new \DateTimeImmutable());
        $item->setExtensionProperty('ceterms:subjectWebpage', 'https://example.edu');
        $item->setExtensionProperty('ob3', ['nested' => ['deep' => true]]);

        $model = $this->makeNormalizer()->normalize($item, 'md');

        self::assertSame('https://example.edu', $model['front_matter']['extensions']['ceterms:subjectWebpage']);
        self::assertArrayNotHasKey('ob3', $model['front_matter']['extensions'] ?? []);
        self::assertStringContainsString('## Extensions', $model['body']);
        self::assertStringContainsString('"ob3"', $model['body']);
    }

    private function makeNormalizer(): LsItemMarkdownNormalizer
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []) => 'https://localhost/uri/'.($params['uri'] ?? $params['id'] ?? 'x')
        );
        $uriGenerator = new UriGenerator($router);
        $api1Uris = new Api1Uris($router, $uriGenerator);
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $objectHelper = $this->createMock(\App\Service\IdentifiableObjectHelper::class);
        $objectHelper->method('findObjectByIdentifier')->willReturn(null);
        $collector = new AssociationRowsCollector($authChecker, $api1Uris, $uriGenerator, $objectHelper);
        $listRenderer = new \App\Service\Markdown\AssociationListRenderer(new HtmlToMarkdownConverter());

        $assocRepo = $this->createMock(\App\Repository\Framework\LsAssociationRepository::class);
        $assocRepo->method('findAllAssociationsForAsSplitArray')->willReturn(['associations' => [], 'inverseAssociations' => []]);
        $credentialRenderer = new \App\Service\Markdown\Renderer\CredentialRenderer(
            new HtmlToMarkdownConverter(),
            new \App\Service\Markdown\CredentialAssociationsResolver($assocRepo, $this->createMock(\App\Repository\Framework\LsItemRepository::class)),
            $uriGenerator,
        );

        $locator = new ItemRendererLocator(
            new DefaultItemRenderer(new HtmlToMarkdownConverter()),
            $credentialRenderer,
        );

        return new LsItemMarkdownNormalizer($api1Uris, $uriGenerator, $collector, $listRenderer, $locator);
    }
}
