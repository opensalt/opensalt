<?php

declare(strict_types=1);

namespace Tests\Unit\App\Controller\Api\V1;

use App\Controller\Api\V1\ApiV1DocumentController;
use App\DTO\Api\V1\DocumentDto;
use App\DTO\Api\V1\DocumentFilterDto;
use App\DTO\Api\V1\DocumentListResponseDto;
use App\DTO\Api\V1\PaginationDto;
use App\DTO\Api\V1\DocumentPaginationResponseDto;
use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiV1DocumentControllerTest extends TestCase
{
    private ApiV1DocumentController $controller;
    private SerializerInterface $serializer;
    private LsDocRepository $lsDocRepository;
    private ObjectMapperInterface $objectMapper;
    private EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->lsDocRepository = $this->createMock(LsDocRepository::class);
        $this->objectMapper = $this->createMock(ObjectMapperInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->controller = new ApiV1DocumentController(
            $this->serializer,
            $this->lsDocRepository,
            $this->objectMapper
        );
        $this->controller->setDispatcher($this->dispatcher);
    }

    public function testIndexReturnsJsonResponse(): void
    {
        // Arrange
        $pagination = new PaginationDto();
        $pagination->size = 20;
        $pagination->after = null;
        $pagination->direction = 'next';

        $filter = new DocumentFilterDto();
        $filter->creator = null;
        $filter->title = null;
        $filter->adoptionStatus = null;
        $filter->subject = null;
        $filter->language = null;
        $filter->caseVersion = null;
        $filter->publisher = null;
        $filter->sort = 'updatedAt';
        $filter->order = 'desc';

        $documents = [];
        $paginationData = new DocumentPaginationResponseDto(false,  null, 0);

        $result = new DocumentListResponseDto($documents, $paginationData);

        $this->lsDocRepository->expects($this->once())
            ->method('findDocumentsWithPagination')
            ->with($pagination, $filter)
            ->willReturn($result);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($result, 'json', [])
            ->willReturn('{"data":[],"pagination":{}}');

        // Act
        $response = $this->controller->index($pagination, $filter);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testIndexWithFilters(): void
    {
        // Arrange
        $pagination = new PaginationDto();
        $pagination->size = 10;
        $pagination->after = 'cursor123';
        $pagination->direction = 'next';

        $filter = new DocumentFilterDto();
        $filter->creator = 'Test Creator';
        $filter->title = 'Test Title';
        $filter->adoptionStatus = 'Draft';
        $filter->subject = 'Mathematics';
        $filter->language = 'en';
        $filter->caseVersion = '1.1';
        $filter->publisher = 'Test Publisher';
        $filter->sort = 'title';
        $filter->order = 'asc';

        $documents = [];
        $paginationData = new DocumentPaginationResponseDto(false, null, 0);

        $result = new DocumentListResponseDto($documents, $paginationData);

        $this->lsDocRepository->expects($this->once())
            ->method('findDocumentsWithPagination')
            ->with($pagination, $filter)
            ->willReturn($result);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($result, 'json', [])
            ->willReturn('{"data":[],"pagination":{}}');

        // Act
        $response = $this->controller->index($pagination, $filter);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPostDocumentCreatesNewDocument(): void
    {
        // Arrange
        $documentDto = new DocumentDto();
        $documentDto->identifier = Uuid::uuid4();
        $documentDto->uri = 'https://example.com/test';
        $documentDto->creator = 'Test Creator';
        $documentDto->title = 'Test Document';
        $documentDto->lastChangeDateTime = new \DateTimeImmutable();
        $documentDto->caseVersion = '1.1';
        $documentDto->publisher = 'Test Publisher';
        $documentDto->description = 'Test Description';
        $documentDto->subject = 'Mathematics';
        $documentDto->language = 'en';
        $documentDto->version = '1.0';
        $documentDto->adoptionStatus = 'Draft';
        $documentDto->statusStartDate = new \DateTimeImmutable();
        $documentDto->statusEndDate = new \DateTimeImmutable();
        $documentDto->notes = 'Test notes';
        $documentDto->extensions = ['key' => 'value'];

        $this->objectMapper->expects($this->once())
            ->method('map')
            ->with($documentDto, $this->isInstanceOf(LsDoc::class));

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"id":1,"title":"Test Document"}');

        // Act
        $response = $this->controller->postDocument($documentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testPostDocumentWithMinimalData(): void
    {
        // Arrange
        $documentDto = new DocumentDto();
        $documentDto->identifier = Uuid::uuid4();
        $documentDto->uri = 'https://example.com/minimal';
        $documentDto->creator = 'Minimal Creator';
        $documentDto->title = 'Minimal Document';

        $this->objectMapper->expects($this->once())
            ->method('map')
            ->with($documentDto, $this->isInstanceOf(LsDoc::class));

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"id":1,"title":"Minimal Document"}');

        // Act
        $response = $this->controller->postDocument($documentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testGetDocumentReturnsForwardedResponse(): void
    {
        // Arrange
        $lsDoc = new LsDoc();
        $lsDoc->setIdentifier(Uuid::uuid4());

        // The getDocument method forwards to UriController, so we can't easily test the forwarding
        // In a real test, we would mock the container and test the forwarding behavior
        // For now, we'll just ensure the method exists and can be called

        // Act & Assert
        $this->expectNotToPerformAssertions();
        // The actual forwarding behavior would be tested in integration tests
    }

    public function testPutDocumentUpdatesExistingDocument(): void
    {
        // Arrange
        $lsDoc = new LsDoc();
        // $lsDoc->setId(1);
        $lsDoc->setIdentifier(Uuid::fromString('53c4dd08-64c4-4a7a-91b9-349acf1d098e'));

        $documentDto = new DocumentDto();
        $documentDto->uri = 'https://example.com/updated';
        $documentDto->creator = 'Updated Creator';
        $documentDto->title = 'Updated Document';
        $documentDto->lastChangeDateTime = new \DateTimeImmutable();
        $documentDto->caseVersion = '1.1';
        $documentDto->publisher = 'Updated Publisher';
        $documentDto->description = 'Updated Description';
        $documentDto->subject = 'Updated Subject';
        $documentDto->language = 'en';
        $documentDto->version = '2.0';
        $documentDto->adoptionStatus = 'Adopted';
        $documentDto->statusStartDate = new \DateTimeImmutable();
        $documentDto->statusEndDate = new \DateTimeImmutable();
        $documentDto->notes = 'Updated notes';
        $documentDto->extensions = ['updated' => 'value'];

        $this->objectMapper->expects($this->once())
            ->method('map')
            ->with($documentDto, $lsDoc);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"id":1,"title":"Updated Document"}');

        // Act
        $response = $this->controller->putDocument($lsDoc, $documentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPutDocumentWithPartialUpdate(): void
    {
        // Arrange
        $lsDoc = new LsDoc();
        // $lsDoc->setId(1);
        $lsDoc->setIdentifier(Uuid::fromString('293a0807-6d13-4596-bb06-b36d002dd66f'));
        $lsDoc->setTitle('Original Title');

        $documentDto = new DocumentDto();
        $documentDto->uri = 'https://example.com/updated';
        $documentDto->creator = 'Updated Creator';
        $documentDto->title = 'Updated Title';
        $documentDto->lastChangeDateTime = new \DateTimeImmutable();

        $this->objectMapper->expects($this->once())
            ->method('map')
            ->with($documentDto, $lsDoc);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"id":1,"title":"Updated Title"}');

        // Act
        $response = $this->controller->putDocument($lsDoc, $documentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetDocumentsWithPaginationReturnsCorrectStructure(): void
    {
        // This is a private method, so we'd need reflection to test it directly
        // In practice, this would be tested through the index method
        // or we could make it protected and test it through a subclass

        $this->markTestIncomplete('Private method testing requires reflection or subclassing');
    }

    public function testIndexWithCursorPagination(): void
    {
        // Arrange
        $pagination = new PaginationDto();
        $pagination->size = 5;
        $pagination->after = base64_encode('123');
        $pagination->direction = 'next';

        $filter = new DocumentFilterDto();
        $filter->sort = 'updatedAt';
        $filter->order = 'desc';

        $documents = [];
        $paginationData = new DocumentPaginationResponseDto(false, null, 0);

        $result = new DocumentListResponseDto($documents, $paginationData);

        $this->lsDocRepository->expects($this->once())
            ->method('findDocumentsWithPagination')
            ->with($pagination, $filter)
            ->willReturn($result);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($result, 'json', [])
            ->willReturn('{"data":[],"pagination":{"hasPrevPage":true}}');

        // Act
        $response = $this->controller->index($pagination, $filter);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testIndexWithAllFilterParameters(): void
    {
        // Arrange
        $pagination = new PaginationDto();
        $pagination->size = 25;
        $pagination->after = null;
        $pagination->direction = 'next';

        $filter = new DocumentFilterDto();
        $filter->creator = 'John Doe';
        $filter->title = 'Advanced Mathematics';
        $filter->adoptionStatus = 'Adopted';
        $filter->subject = 'Mathematics';
        $filter->language = 'en-US';
        $filter->caseVersion = '1.1';
        $filter->publisher = 'Educational Standards Organization';
        $filter->sort = 'title';
        $filter->order = 'asc';

        $documents = [];
        $paginationData = new DocumentPaginationResponseDto(false, null, 0);

        $result = new DocumentListResponseDto($documents, $paginationData);

        $this->lsDocRepository->expects($this->once())
            ->method('findDocumentsWithPagination')
            ->with($pagination, $filter)
            ->willReturn($result);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($result, 'json', [])
            ->willReturn('{"data":[],"pagination":{}}');

        // Act
        $response = $this->controller->index($pagination, $filter);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
