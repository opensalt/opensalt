<?php

declare(strict_types=1);

namespace Tests\Unit\App\Controller\Api\V1;

use App\Controller\Api\V1\ApiV1PackageController;
use App\DTO\Api\V1\DocumentDto;
use App\DTO\Api\V1\DocumentFilterDto;
use App\DTO\Api\V1\DocumentPaginationDto;
use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiV1PackageControllerTest extends TestCase
{
    private ApiV1PackageController $controller;
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

        $this->controller = new ApiV1PackageController(
            $this->serializer,
            $this->lsDocRepository,
            $this->objectMapper
        );
        $this->controller->setDispatcher($this->dispatcher);
    }

    public function testIndexReturnsJsonResponse(): void
    {
        // Mock the repository to return empty results
        $queryBuilderMock = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryMock = $this->createMock(\Doctrine\ORM\Query::class);
        $queryMock->method('getResult')->willReturn([]);

        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryBuilderMock->method('select')->willReturnSelf();
        $queryBuilderMock->method('leftJoin')->willReturnSelf();
        $queryBuilderMock->method('andWhere')->willReturnSelf();
        $queryBuilderMock->method('setParameter')->willReturnSelf();
        $queryBuilderMock->method('orderBy')->willReturnSelf();
        $queryBuilderMock->method('addOrderBy')->willReturnSelf();
        $queryBuilderMock->method('setMaxResults')->willReturnSelf();

        $this->lsDocRepository->method('createQueryBuilder')->willReturn($queryBuilderMock);

        // Create DTOs
        $pagination = new DocumentPaginationDto();
        $pagination->limit = 20;
        $pagination->cursor = null;
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

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"data":[],"pagination":{"hasNextPage":false,"hasPrevPage":false,"nextCursor":null,"prevCursor":null,"total":0}}');

        // Act
        $response = $this->controller->index($pagination, $filter);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testIndexWithFilters(): void
    {
        // Create DTOs with filter values
        $pagination = new DocumentPaginationDto();
        $pagination->limit = 10;
        $pagination->cursor = 'cursor123';
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

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"data":[],"pagination":{"hasNextPage":false,"hasPrevPage":false,"nextCursor":null,"prevCursor":null,"total":0}}');

        // Act
        $response = $this->controller->index($pagination, $filter);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPostPackageCreatesNewDocument(): void
    {
        // Arrange
        $uuid = $this->createMock(\Ramsey\Uuid\UuidInterface::class);
        $uuid->method('toString')->willReturn('550e8400-e29b-41d4-a716-446655440000');

        $documentDto = new DocumentDto();
        $documentDto->identifier = $uuid;
        $documentDto->uri = 'https://example.com/doc';
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
        $response = $this->controller->postPackage($documentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testPostPackageWithMinimalData(): void
    {
        // Arrange
        $uuid = $this->createMock(\Ramsey\Uuid\UuidInterface::class);
        $uuid->method('toString')->willReturn('550e8400-e29b-41d4-a716-446655440001');

        $documentDto = new DocumentDto();
        $documentDto->identifier = $uuid;
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
        $response = $this->controller->postPackage($documentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testGetPackageForwardsToUriController(): void
    {
        // The getPackage method forwards to UriController
        // This would typically be tested in integration tests
        $this->markTestIncomplete('Forwarding behavior should be tested in integration tests');
    }

    public function testPutPackageUpdatesExistingDocument(): void
    {
        // Arrange
        $lsDoc = new LsDoc();
        $lsDoc->setIdentifier('550e8400-e29b-41d4-a716-446655440002');

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
        $response = $this->controller->putPackage($lsDoc, $documentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPutPackageWithPartialUpdate(): void
    {
        // Arrange
        $lsDoc = new LsDoc();
        $lsDoc->setIdentifier('550e8400-e29b-41d4-a716-446655440004');
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
        $response = $this->controller->putPackage($lsDoc, $documentDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeletePackageRemovesDocument(): void
    {
        // Arrange
        $lsDoc = new LsDoc();
        $lsDoc->setIdentifier('550e8400-e29b-41d4-a716-446655440003');

        // Act
        $response = $this->controller->deletePackage($lsDoc);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testIndexWithCursorPagination(): void
    {
        // Create DTOs with pagination values
        $pagination = new DocumentPaginationDto();
        $pagination->limit = 5;
        $pagination->cursor = base64_encode('123');
        $pagination->direction = 'next';

        $filter = new DocumentFilterDto();
        $filter->sort = 'updatedAt';
        $filter->order = 'desc';

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"data":[],"pagination":{"hasNextPage":false,"hasPrevPage":true,"nextCursor":null,"prevCursor":"prev123","total":10}}');

        // Act
        $response = $this->controller->index($pagination, $filter);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testIndexWithAllFilterParameters(): void
    {
        // Create DTOs with all filter parameters
        $pagination = new DocumentPaginationDto();
        $pagination->limit = 25;
        $pagination->cursor = null;
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

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"data":[],"pagination":{"hasNextPage":false,"hasPrevPage":false,"nextCursor":null,"prevCursor":null,"total":0}}');

        // Act
        $response = $this->controller->index($pagination, $filter);

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
}
