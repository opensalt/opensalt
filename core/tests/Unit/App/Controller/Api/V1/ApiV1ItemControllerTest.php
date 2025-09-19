<?php

declare(strict_types=1);

namespace Tests\Unit\App\Controller\Api\V1;

use App\Controller\Api\V1\ApiV1ItemController;
use App\DTO\Api\V1\ItemDto;
use App\Entity\Framework\LsItem;
use App\Event\CommandEvent;
use App\Repository\Framework\LsItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
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
            $this->itemRepository,
            $this->entityManager,
            $this->objectMapper
        );
        $this->controller->setDispatcher($this->dispatcher);

        // Mock the container and router for generateUrl
        $router = $this->createMock(\Symfony\Component\Routing\RouterInterface::class);
        $router->method('generate')->willReturn('/api/v1/packages/some-doc/items/some-item');
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')->willReturnCallback(function ($id) use ($router) {
            if ('router' === $id) {
                return $router;
            }

            return null;
        });
        $this->controller->setContainer($container);
    }

    public function testPostItemCreatesNewItem(): void
    {
        $uuid = Uuid::uuid4()->toString();
        // Arrange
        $itemDto = new ItemDto();
        $itemDto->identifier = $this->createMock(\Ramsey\Uuid\UuidInterface::class);
        $itemDto->identifier->expects($this->once())
            ->method('toString')
            ->willReturn($uuid);
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
            ->with($uuid)
            ->willReturn($lsItem);

        $lsItem->expects($this->once())
            ->method('getUri')
            ->willReturn('https://example.com/item');

        $lsItem->expects($this->once())
            ->method('getLsDoc')
            ->willReturn($lsDoc);

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

        $this->dispatcher->expects($this->once())
            ->method('dispatch');

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{"data":{"id":1,"fullStatement":"Test item statement"}}');

        // Act
        $response = $this->controller->postItem($lsDoc, $itemDto);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('/api/v1/packages/some-doc/items/some-item', $response->headers->get('Location'));
    }

    public function testGetItemReturnsItem(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($lsItem, 'json', [])
            ->willReturn('{"id":1,"fullStatement":"Test item"}');

        // Act
        $response = $this->controller->getItem($lsItem, $lsDoc);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPutItemUpdatesExistingItem(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);
        $lsItem->expects($this->atMost(2))
            ->method('getUri')
            ->willReturn('https://example.com/existing');
        $lsItem->expects($this->atMost(2))
            ->method('getIdentifier')
            ->willReturn(Uuid::uuid4()->toString());

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

        $this->dispatcher->expects($this->once())
            ->method('dispatch');

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
            ->willReturn('1ba1ebf0-72a8-41db-a6fb-55fb5a0a92e4');

        $itemDto = new ItemDto();
        $itemDto->identifier = $this->createMock(\Ramsey\Uuid\UuidInterface::class);
        $itemDto->identifier->expects($this->once())
            ->method('toString')
            ->willReturn('aaa1ebf0-72a8-41db-a6fb-55fb5a0a92e4');

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
        $lsItem->expects($this->atMost(2))
            ->method('getIdentifier')
            ->willReturn(Uuid::uuid4()->toString());
        $lsItem->method('getUri')->willReturn('https://example.com/existing');

        $itemDto = new ItemDto();
        $itemDto->uri = 'https://example.com/different';

        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('The uri must not be changed.');

        // Act
        $this->controller->putItem($lsItem, $lsDoc, $itemDto);
    }

    public function testDeleteItemRemovesItem(): void
    {
        // Arrange
        $lsItem = $this->createMock(LsItem::class);
        $lsDoc = $this->createMock(\App\Entity\Framework\LsDoc::class);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CommandEvent::class));

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
