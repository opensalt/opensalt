<?php

declare(strict_types=1);

namespace Tests\Unit\App\Mcp\OpenSalt;

use App\Entity\Framework\LsDoc;
use App\Mcp\OpenSalt\OpenSaltMcpPayloadFactory;
use App\Mcp\OpenSalt\OpenSaltMcpQueryService;
use App\Mcp\OpenSalt\OpenSaltMcpTools;
use App\VectorSearch\Service\VectorSearchService;
use PHPUnit\Framework\TestCase;

class OpenSaltMcpToolsTest extends TestCase
{
    private OpenSaltMcpQueryService $queryService;
    private OpenSaltMcpPayloadFactory $payloadFactory;
    private VectorSearchService $vectorSearchService;
    private OpenSaltMcpTools $tools;

    protected function setUp(): void
    {
        $this->queryService = $this->createMock(OpenSaltMcpQueryService::class);
        $this->payloadFactory = $this->createMock(OpenSaltMcpPayloadFactory::class);
        $this->vectorSearchService = $this->createMock(VectorSearchService::class);

        $this->tools = new OpenSaltMcpTools(
            $this->queryService,
            $this->payloadFactory,
            $this->vectorSearchService,
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

    public function testGetDocumentReturnsDocumentWhenFound(): void
    {
        $doc = $this->createMock(LsDoc::class);
        $this->queryService->method('getPublicDocumentByIdentifier')->willReturn($doc);

        $expectedDetails = ['id' => 1, 'title' => 'Test Doc'];
        $this->payloadFactory->method('documentDetails')->willReturn($expectedDetails);

        $result = $this->tools->getDocument('test-doc');

        $this->assertArrayHasKey('document', $result);
        $this->assertSame($expectedDetails, $result['document']);
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
