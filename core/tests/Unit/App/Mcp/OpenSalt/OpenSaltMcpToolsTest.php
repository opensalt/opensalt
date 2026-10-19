<?php

declare(strict_types=1);

namespace Tests\Unit\App\Mcp\OpenSalt;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Mcp\OpenSalt\OpenSaltMcpPayloadFactory;
use App\Mcp\OpenSalt\OpenSaltMcpQueryService;
use App\Mcp\OpenSalt\OpenSaltMcpTools;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use App\Serializer\Markdown\GenericMarkdownNormalizer;
use App\Serializer\Markdown\LsDocMarkdownNormalizer;
use App\Serializer\Markdown\LsItemMarkdownNormalizer;
use App\Serializer\Markdown\MarkdownEncoder;
use App\Service\Api1Uris;
use App\Service\IdentifiableObjectHelper;
use App\Service\Markdown\AssociationListRenderer;
use App\Service\Markdown\AssociationRowsCollector;
use App\Service\Markdown\CredentialAssociationsResolver;
use App\Service\Markdown\HtmlToMarkdownConverter;
use App\Service\Markdown\ItemRendererLocator;
use App\Service\Markdown\Renderer\AssessmentRenderer;
use App\Service\Markdown\Renderer\CourseRenderer;
use App\Service\Markdown\Renderer\CredentialRenderer;
use App\Service\Markdown\Renderer\DefaultItemRenderer;
use App\Service\Markdown\Renderer\IdentifierRenderer;
use App\Service\Markdown\Renderer\JobRenderer;
use App\Service\Markdown\Renderer\OrganizationRenderer;
use App\Service\Markdown\Renderer\PublicKeyRenderer;
use App\Service\UriGenerator;
use App\VectorSearch\Service\VectorSearchService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Serializer;

class OpenSaltMcpToolsTest extends TestCase
{
    private OpenSaltMcpQueryService $queryService;
    private OpenSaltMcpPayloadFactory $payloadFactory;
    private VectorSearchService $vectorSearchService;
    private UriGenerator $uriGenerator;
    private Serializer $serializer;
    private OpenSaltMcpTools $tools;

    protected function setUp(): void
    {
        $this->queryService = $this->createMock(OpenSaltMcpQueryService::class);
        $this->payloadFactory = $this->createMock(OpenSaltMcpPayloadFactory::class);
        $this->vectorSearchService = $this->createMock(VectorSearchService::class);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []) => 'https://localhost/uri/'.($params['uri'] ?? $params['id'] ?? '')
        );
        $this->uriGenerator = new UriGenerator($router);
        $api1Uris = new Api1Uris($router, $this->uriGenerator);
        $htmlConverter = new HtmlToMarkdownConverter();

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);
        $objectHelper = $this->createMock(IdentifiableObjectHelper::class);
        $objectHelper->method('findObjectByIdentifier')->willReturn(null);

        $collector = new AssociationRowsCollector($authChecker, $api1Uris, $this->uriGenerator, $objectHelper);
        $listRenderer = new AssociationListRenderer($htmlConverter);

        $credentialResolver = new CredentialAssociationsResolver(
            $this->createMock(LsAssociationRepository::class),
            $this->createMock(LsItemRepository::class),
        );
        $locator = new ItemRendererLocator(
            new DefaultItemRenderer($htmlConverter),
            new CredentialRenderer($htmlConverter, $credentialResolver, $this->uriGenerator),
            new CourseRenderer($htmlConverter),
            new JobRenderer($htmlConverter),
            new AssessmentRenderer($htmlConverter),
            new OrganizationRenderer($htmlConverter),
            new IdentifierRenderer($htmlConverter),
            new PublicKeyRenderer($htmlConverter),
        );

        $docRepository = $this->createMock(LsDocRepository::class);
        $docRepository->method('findItemTocRows')->willReturn([]);
        $docRepository->method('findItemParentMap')->willReturn([]);

        $this->serializer = new Serializer(
            [
                new LsItemMarkdownNormalizer($api1Uris, $this->uriGenerator, $collector, $listRenderer, $locator),
                new LsDocMarkdownNormalizer($this->uriGenerator, $htmlConverter, $docRepository),
                new GenericMarkdownNormalizer($this->uriGenerator, $collector, $htmlConverter),
            ],
            [new MarkdownEncoder()]
        );

        $this->tools = new OpenSaltMcpTools(
            $this->queryService,
            $this->payloadFactory,
            $this->vectorSearchService,
            $this->serializer,
            $this->uriGenerator,
        );
    }

    public function testListDocumentsCallsQueryService(): void
    {
        $this->queryService->method('listPublicDocuments')->willReturn([]);

        $result = $this->tools->listDocuments();

        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('query', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('documents', $result);
        $this->assertSame(0, $result['count']);
        $this->assertSame([], $result['documents']);
    }

    public function testListDocumentsNormalizesLimit(): void
    {
        $this->queryService->method('listPublicDocuments')->willReturn([]);

        $result = $this->tools->listDocuments(limit: 500);

        $this->assertSame(100, $result['limit']);
    }

    public function testListDocumentsNormalizesNegativeLimit(): void
    {
        $this->queryService->method('listPublicDocuments')->willReturn([]);

        $result = $this->tools->listDocuments(limit: -1);

        $this->assertSame(20, $result['limit']);
    }

    public function testGetDocumentReturnsErrorWhenNotFound(): void
    {
        $this->queryService->method('getPublicDocumentByIdentifier')->willReturn(null);

        $result = $this->tools->getDocument('non-existent');

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('non-existent', $result['document_identifier']);
    }

    public function testGetDocumentMarkdownByDefault(): void
    {
        $doc = new LsDoc();
        $doc->setIdentifier('22222222-2222-4222-8222-222222222222');
        $doc->setTitle('Test Doc');
        $doc->setCreator('Test Creator');
        $doc->setAdoptionStatus('Draft');
        $doc->setChangedAt(new \DateTimeImmutable('2026-10-01T00:00:00+00:00'));
        $this->queryService->method('getPublicDocumentByIdentifier')->willReturn($doc);

        $result = $this->tools->getDocument('test-doc');

        $this->assertIsString($result);
        $this->assertStringContainsString('objectType: Document', $result);
        $this->assertStringContainsString('title: \'Test Doc\'', $result);
        $this->assertStringContainsString('# Test Doc', $result);
        $this->assertStringNotContainsString('"document"', $result);
    }

    public function testGetDocumentJsonFormatKeepsStructuredPayload(): void
    {
        $doc = $this->createMock(LsDoc::class);
        $this->queryService->method('getPublicDocumentByIdentifier')->willReturn($doc);

        $expectedDetails = ['id' => 1, 'title' => 'Test Doc'];
        $this->payloadFactory->method('documentDetails')->willReturn($expectedDetails);

        $result = $this->tools->getDocument('test-doc', format: 'json');

        $this->assertArrayHasKey('document', $result);
        $this->assertSame($expectedDetails, $result['document']);
    }

    public function testGetItemMarkdownByDefault(): void
    {
        $doc = new LsDoc();
        $doc->setTitle('Test framework');

        $item = new LsItem();
        $item->setLsDoc($doc);
        $item->setIdentifier('11111111-1111-4111-8111-111111111111');
        $item->setAbbreviatedStatement('Test item');
        $item->setChangedAt(new \DateTimeImmutable());

        $this->queryService->method('getPublicItemByIdentifier')->willReturn($item);
        $this->queryService->method('findAssociationRelatedItems')->willReturn([]);

        $result = $this->tools->getItem('test-item');

        $this->assertIsString($result);
        $this->assertStringContainsString('objectType: Item', $result);
        $this->assertStringContainsString('# Test item', $result);
        $this->assertStringContainsString('## Ancestor Path', $result);
    }

    public function testGetItemMarkdownAppendsRelatedItems(): void
    {
        $doc = new LsDoc();
        $doc->setTitle('Test framework');

        $item = new LsItem();
        $item->setLsDoc($doc);
        $item->setAbbreviatedStatement('Test item');
        $item->setChangedAt(new \DateTimeImmutable());

        $related = new LsItem();
        $related->setLsDoc($doc);
        $related->setAbbreviatedStatement('Related item');
        $related->setChangedAt(new \DateTimeImmutable());

        $this->queryService->method('getPublicItemByIdentifier')->willReturn($item);
        $this->queryService->method('findAssociationRelatedItems')->willReturn([
            ['item' => $related, 'relationCount' => 2, 'relations' => []],
        ]);

        $result = $this->tools->getItem('test-item');

        $this->assertIsString($result);
        $this->assertStringContainsString('## Related Items', $result);
        $this->assertStringContainsString('- [Related item]', $result);
        $this->assertStringContainsString('2 relations', $result);
    }

    public function testGetItemJsonFormatKeepsStructuredPayload(): void
    {
        $item = $this->createMock(LsItem::class);
        $this->queryService->method('getPublicItemByIdentifier')->willReturn($item);

        $expectedDetails = ['id' => 1, 'title' => 'Test item'];
        $this->payloadFactory->method('itemDetails')->willReturn($expectedDetails);

        $result = $this->tools->getItem('test-item', includeRelated: false, format: 'json');

        $this->assertArrayHasKey('item', $result);
        $this->assertSame($expectedDetails, $result['item']);
    }

    public function testGetItemReturnsErrorWhenNotFound(): void
    {
        $this->queryService->method('getPublicItemByIdentifier')->willReturn(null);

        $result = $this->tools->getItem('non-existent');

        $this->assertArrayHasKey('error', $result);
    }

    public function testListItemsCallsQueryService(): void
    {
        $this->queryService->method('listPublicItems')->willReturn([]);

        $result = $this->tools->listItems();

        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('query', $result);
        $this->assertArrayHasKey('document_identifier', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('items', $result);
        $this->assertSame(0, $result['count']);
        $this->assertSame([], $result['items']);
    }

    public function testSemanticSearchRejectsEmptyQuery(): void
    {
        $result = $this->tools->semanticSearchItems('');

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Query must not be empty.', $result['error']);
    }
}
