<?php

declare(strict_types=1);

namespace Tests\Unit\App\Controller\Api\V1;

use App\Command\Framework\DeleteItemCommand;
use App\Controller\Api\V1\ApiV1ItemController;
use App\DTO\Api\V1\ItemDto;
use App\DTO\Api\V1\PatchDto;
use App\DTO\Api\V1\PatchOperation;
use App\Entity\Framework\LsItem;
use App\Event\CommandEvent;
use App\Repository\Framework\LsItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiV1ItemControllerTest extends TestCase
{
    private ApiV1ItemController $controller;
    private SerializerInterface $serializer;
    private LsItemRepository $itemRepository;
    private EntityManagerInterface $entityManager;
    private PropertyAccessorInterface $propertyAccessor;
    private EventDispatcherInterface $dispatcher;
    private ObjectMapperInterface $objectMapper;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->itemRepository = $this->createMock(LsItemRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->objectMapper = $this->createMock(ObjectMapperInterface::class);

        $this->controller = new ApiV1ItemController(
            $this->serializer,
            $this->propertyAccessor,
            $this->itemRepository,
            $this->entityManager,
            $this->objectMapper
        );
        $this->controller->setDispatcher($this->dispatcher);
    }

    public function testPostItemCreatesNewItem(): void
    {
        // Arrange
        $itemDto = new ItemDto();
        $itemDto->identifier = $this->createMock(\Ramsey\Uuid\UuidInterface::class);
        $itemDto->identifier->expects($this->once())
            ->method('toString')
            ->willReturn('test-item-id');
        $itemDto->uri = 'https://example.com/item';
        $itemDto->fullStatement = 'Test item statement';
        $itemDto->abbreviatedStatement = 'Test item';
        $itemDto->conceptKeywords = ['test', 'item'];
        $itemDto->notes = 'Test notes';
        $itemDto->language = 'en';
        $itemDto->educationLevel = '09';
        $itemDto->humanCodingScheme = 'test-scheme';
        $itemDto->alternativeLabel = 'Test Label';
        $itemDto->listEnumeration = '1';
        $itemDto->statusStartDate = new \DateTimeImmutable();
        $itemDto->statusEndDate = new \DateTimeImmutable();
        $itemDto->lastChangeDateTime = new \DateTimeImmutable();
        $itemDto->extensions = ['key' => 'value'];

        $lsItem = $this->createMock(LsItem::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $lsDoc->expects($this->once())
            ->method('createItem')
            ->with('test-item-id')
            ->willReturn($lsItem);

        // Expect ObjectMapper to be called for direct property mappings
        $this->objectMapper->expects($this->once())
            ->method('map')
            ->with($itemDto, $lsItem);

        // Expect special handling methods to be called
        $lsItem->expects($this->once())
            ->method('setUri')
            ->with('https://example.com/item');
        $lsItem->expects($this->once())
            ->method('setEducationalAlignment')
            ->with('09');
        $lsItem->expects($this->once())
            ->method('setChangedAt')
            ->with($itemDto->lastChangeDateTime);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($lsItem, 'json', [])
            ->willReturn('{"id":1,"fullStatement":"Test item statement"}');

        // Act
        $response = $this->controller->postItem($lsDoc, $itemDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testGetItemForwardsToUriController(): void
    {
        // The getItem method forwards to UriController
        // This would typically be tested in integration tests
        $this->markTestIncomplete('Forwarding behavior should be tested in integration tests');
    }

    public function testPutItemUpdatesExistingItem(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);
        $lsItem->expects($this->once())
            ->method('getUri')
            ->willReturn('https://example.com/existing');

        $itemDto = new ItemDto();
        $itemDto->uri = null; // Set to null so it doesn't trigger the URI validation
        $itemDto->fullStatement = 'Updated statement';
        $itemDto->abbreviatedStatement = 'Updated item';
        $itemDto->conceptKeywords = ['updated', 'item'];
        $itemDto->notes = 'Updated notes';
        $itemDto->language = 'en';
        $itemDto->educationLevel = '10';
        $itemDto->humanCodingScheme = 'updated-scheme';
        $itemDto->alternativeLabel = 'Updated Label';
        $itemDto->listEnumeration = '2';
        $itemDto->statusStartDate = new \DateTimeImmutable();
        $itemDto->statusEndDate = new \DateTimeImmutable();
        $itemDto->lastChangeDateTime = new \DateTimeImmutable();
        $itemDto->extensions = ['updated' => 'value'];

        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        // Expect ObjectMapper to be called for direct property mappings
        $this->objectMapper->expects($this->once())
            ->method('map')
            ->with($itemDto, $lsItem);

        // Expect special handling methods to be called
        $lsItem->expects($this->once())
            ->method('setUri')
            ->with('https://example.com/existing'); // uri is null, so it uses existing value
        $lsItem->expects($this->once())
            ->method('setEducationalAlignment')
            ->with('10');
        $lsItem->expects($this->once())
            ->method('setChangedAt')
            ->with($itemDto->lastChangeDateTime);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($lsItem, 'json', [])
            ->willReturn('{"id":1,"fullStatement":"Updated statement"}');

        // Act
        $response = $this->controller->putItem($lsItem, $lsDoc, $itemDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPutItemThrowsExceptionWhenIdentifierChanged(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);
        $lsItem->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('existing-item-id');

        $itemDto = new ItemDto();
        $itemDto->identifier = $this->createMock(\Ramsey\Uuid\UuidInterface::class);
        $itemDto->identifier->expects($this->once())
            ->method('toString')
            ->willReturn('different-item-id');

        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('The identifier must not be changed.');

        // Act
        $this->controller->putItem($lsItem, $lsDoc, $itemDto);
    }

    public function testPutItemThrowsExceptionWhenUriChanged(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);
        $lsItem->method('getIdentifier')->willReturn('existing-item-id');
        $lsItem->method('getUri')->willReturn('https://example.com/existing');

        $itemDto = new ItemDto();
        $itemDto->uri = 'https://example.com/different';

        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('The uri must not be changed.');

        // Act
        $this->controller->putItem($lsItem, $lsDoc, $itemDto);
    }

    public function testPatchItemAppliesPatchOperations(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $patchOperation = new PatchOperation();
        $patchOperation->op = 'replace';
        $patchOperation->path = '/fullStatement';
        $patchOperation->value = 'Patched statement';

        $patchDto = new PatchDto();
        $patchDto->patch = [$patchOperation];

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsItem, 'fullStatement')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($lsItem, 'fullStatement', 'Patched statement');

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($lsItem, 'json', [])
            ->willReturn('{"id":1,"fullStatement":"Patched statement"}');

        // Act
        $response = $this->controller->patchItem($lsItem, $lsDoc, $patchDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPatchItemThrowsExceptionForInvalidOperation(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $patchOperation = new PatchOperation();
        $patchOperation->op = 'invalid';
        $patchOperation->path = '/fullStatement';

        $patchDto = new PatchDto();
        $patchDto->patch = [$patchOperation];

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('Unsupported patch operation: invalid');

        // Act
        $this->controller->patchItem($lsItem, $lsDoc, $patchDto);
    }

    public function testDeleteItemRemovesItem(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new CommandEvent(new DeleteItemCommand($lsItem)));

        // Act
        $response = $this->controller->deleteItem($lsItem, $lsDoc);

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
        $lsItem = $this->createMock(LsItem::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsItem, '[newProperty]')
            ->willReturn(false);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($lsItem, '[newProperty]', 'new value');

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }

    public function testApplyReplaceOperationReplacesExistingProperty(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsItem, '[existingProperty]')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($lsItem, '[existingProperty]', 'updated value');

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }

    public function testApplyRemoveOperationRemovesProperty(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsItem, '[propertyToRemove]')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($lsItem, '[propertyToRemove]', null);

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }

    public function testApplyTestOperationValidatesPropertyValue(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsItem, '[testProperty]')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($lsItem, '[testProperty]')
            ->willReturn('expected value');

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }

    public function testApplyTestOperationThrowsExceptionForMismatch(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);

        $this->propertyAccessor->expects($this->once())
            ->method('isReadable')
            ->with($lsItem, '[testProperty]')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($lsItem, '[testProperty]')
            ->willReturn('actual value');

        // Act - we'd need to call the private method via reflection
        $this->markTestIncomplete('Private method testing requires reflection');
    }
}
