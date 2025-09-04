<?php

declare(strict_types=1);

namespace Tests\Unit\App\Controller\Api\V1;

use App\Command\Framework\AddAssociationCommand;
use App\Command\Framework\DeleteAssociationCommand;
use App\Command\Framework\UpdateAssociationCommand;
use App\Controller\Api\V1\ApiV1AssociationController;
use App\DTO\Api\V1\AssociationDto;
use App\DTO\Api\V1\PatchDto;
use App\DTO\Api\V1\PatchOperation;
use App\Entity\Framework\LsAssociation;
use App\Repository\Framework\LsAssociationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiV1AssociationControllerTest extends TestCase
{
    private ApiV1AssociationController $controller;
    private SerializerInterface $serializer;
    private LsAssociationRepository $associationRepository;
    private EntityManagerInterface $entityManager;
    private PropertyAccessorInterface $propertyAccessor;
    private EventDispatcherInterface $dispatcher;
    private ObjectMapperInterface $objectMapper;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->associationRepository = $this->createMock(LsAssociationRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->objectMapper = $this->createMock(ObjectMapperInterface::class);

        $this->controller = new ApiV1AssociationController(
            $this->serializer,
            $this->propertyAccessor,
            $this->associationRepository,
            $this->entityManager,
            $this->objectMapper
        );
        $this->controller->setDispatcher($this->dispatcher);
    }

    public function testPostAssociationCreatesNewAssociation(): void
    {
        $uuid = Uuid::uuid4()->toString();
        // Arrange
        $associationDto = new AssociationDto();
        $associationDto->identifier = $this->createMock(\Ramsey\Uuid\UuidInterface::class);
        $associationDto->identifier->expects($this->once())
            ->method('toString')
            ->willReturn($uuid);
        $associationDto->uri = 'https://example.com/association';
        $associationDto->associationType = 'isRelatedTo';
        $associationDto->originNodeIdentifier = Uuid::uuid4()->toString();
        $associationDto->destinationNodeIdentifier = Uuid::uuid4()->toString();
        $associationDto->subtype = 'test-subtype';
        $associationDto->sequenceNumber = 1;
        $associationDto->lastChangeDateTime = new \DateTimeImmutable();
        $associationDto->extensions = ['key' => 'value'];

        $lsAssociation = $this->createMock(LsAssociation::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $lsDoc->expects($this->once())
            ->method('createAssociation')
            ->with($uuid)
            ->willReturn($lsAssociation);

        $lsAssociation->expects($this->once())
            ->method('getUri')
            ->willReturn('https://example.com/association');

        // Expect ObjectMapper to be called for direct property mappings
        $this->objectMapper->expects($this->once())
            ->method('map')
            ->with($associationDto, $lsAssociation);

        // Expect special handling methods to be called
        $lsAssociation->expects($this->once())
            ->method('setUri')
            ->with('https://example.com/association');
        $lsAssociation->expects($this->once())
            ->method('setChangedAt')
            ->with($associationDto->lastChangeDateTime);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with(['data' => $lsAssociation], 'json', [])
            ->willReturn('{"data":{"id":1,"type":"Is Related To"}}');

        // Act
        $response = $this->controller->postAssociation($lsDoc, $associationDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testGetAssociationForwardsToUriController(): void
    {
        // The getAssociation method forwards to UriController
        // This would typically be tested in integration tests
        $this->markTestIncomplete('Forwarding behavior should be tested in integration tests');
    }

    public function testPutAssociationUpdatesExistingAssociation(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);
        $lsAssociation->expects($this->atMost(2))
            ->method('getUri')
            ->willReturn('https://example.com/existing');
        $lsAssociation->expects($this->atMost(2))
            ->method('getIdentifier')
            ->willReturn(Uuid::uuid4()->toString());

        $associationDto = new AssociationDto();
        $associationDto->uri = null; // Set to null so it doesn't trigger the URI validation
        $associationDto->associationType = 'isPartOf';
        $associationDto->originNodeIdentifier = Uuid::uuid4()->toString();
        $associationDto->destinationNodeIdentifier = Uuid::uuid4()->toString();
        $associationDto->subtype = 'updated-subtype';
        $associationDto->sequenceNumber = 2;
        $associationDto->lastChangeDateTime = new \DateTimeImmutable();
        $associationDto->extensions = ['updated' => 'value'];

        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        // Expect ObjectMapper to be called for direct property mappings
        $this->objectMapper->expects($this->once())
            ->method('map')
            ->with($associationDto, $lsAssociation);

        // Expect special handling methods to be called
        $lsAssociation->expects($this->once())
            ->method('setUri')
            ->with('https://example.com/existing'); // uri is null, so it uses existing value
        $lsAssociation->expects($this->once())
            ->method('setChangedAt')
            ->with($associationDto->lastChangeDateTime);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($lsAssociation, 'json', [])
            ->willReturn('{"id":1,"type":"Is Part Of"}');

        // Act
        $response = $this->controller->putAssociation($lsAssociation, $lsDoc, $associationDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPutAssociationThrowsExceptionWhenIdentifierChanged(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);
        $lsAssociation->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('1ba1ebf0-72a8-41db-a6fb-55fb5a0a92e4');

        $associationDto = new AssociationDto();
        $associationDto->identifier = $this->createMock(\Ramsey\Uuid\UuidInterface::class);
        $associationDto->identifier->expects($this->once())
            ->method('toString')
            ->willReturn('aaa1ebf0-72a8-41db-a6fb-55fb5a0a92e4');

        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('The identifier must not be changed.');

        // Act
        $this->controller->putAssociation($lsAssociation, $lsDoc, $associationDto);
    }

    public function testPutAssociationThrowsExceptionWhenUriChanged(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);
        $lsAssociation->expects($this->atMost(2))
            ->method('getIdentifier')
            ->willReturn(Uuid::uuid4()->toString());
        $lsAssociation->method('getUri')->willReturn('https://example.com/existing');

        $associationDto = new AssociationDto();
        $associationDto->uri = 'https://example.com/different';

        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('The uri must not be changed.');

        // Act
        $this->controller->putAssociation($lsAssociation, $lsDoc, $associationDto);
    }

    public function testPatchAssociationAppliesPatchOperations(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $patchOperation = new PatchOperation();
        $patchOperation->op = 'replace';
        $patchOperation->path = '/type';
        $patchOperation->value = 'Patched association type';

        $patchDto = new PatchDto();
        $patchDto->patch = [$patchOperation];

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsAssociation, 'type')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($lsAssociation, 'type', 'Patched association type');

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($lsAssociation, 'json', [])
            ->willReturn('{"id":1,"type":"Patched association type"}');

        // Act
        $response = $this->controller->patchAssociation($lsAssociation, $lsDoc, $patchDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPatchAssociationThrowsExceptionForInvalidOperation(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $patchOperation = new PatchOperation();
        $patchOperation->op = 'invalid';
        $patchOperation->path = '/type';

        $patchDto = new PatchDto();
        $patchDto->patch = [$patchOperation];

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('Unsupported patch operation: invalid');

        // Act
        $this->controller->patchAssociation($lsAssociation, $lsDoc, $patchDto);
    }

    public function testDeleteAssociationRemovesAssociation(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new \App\Event\CommandEvent(new DeleteAssociationCommand($lsAssociation)));

        // Act
        $response = $this->controller->deleteAssociation($lsAssociation, $lsDoc);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testJsonPointerToPropertyPathConvertsCorrectly(): void
    {
        // This is a private method, so we'd need reflection to test it
        // For now, we'll test it indirectly through the patch operations
        $this->markTestIncomplete('Private method testing requires reflection');
    }

    public function testApplyAddOperationAddsNewProperty(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsAssociation, '[newProperty]')
            ->willReturn(false);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($lsAssociation, '[newProperty]', 'new value');

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }

    public function testApplyReplaceOperationReplacesExistingProperty(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsAssociation, '[existingProperty]')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($lsAssociation, '[existingProperty]', 'updated value');

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }

    public function testApplyRemoveOperationRemovesProperty(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsAssociation, '[propertyToRemove]')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($lsAssociation, '[propertyToRemove]', null);

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }

    public function testApplyTestOperationValidatesPropertyValue(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsAssociation, '[testProperty]')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($lsAssociation, '[testProperty]')
            ->willReturn('expected value');

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }

    public function testApplyTestOperationThrowsExceptionForMismatch(): void
    {
        // Arrange
        $lsAssociation = $this->createMock(LsAssociation::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsAssociation, '[testProperty]')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($lsAssociation, '[testProperty]')
            ->willReturn('actual value');

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }
}
