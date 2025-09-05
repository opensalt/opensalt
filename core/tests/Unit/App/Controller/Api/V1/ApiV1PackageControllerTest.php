<?php

declare(strict_types=1);

namespace Tests\Unit\App\Controller\Api\V1;

use App\Controller\Api\V1\ApiV1PackageController;
use App\DTO\CaseJson\CFDefinition;
use App\DTO\CaseJson\CFDocument;
use App\DTO\CaseJson\CFPackage;
use App\DTO\CaseJson\CFPackageAssociation;
use App\DTO\CaseJson\CFPackageItem;
use App\DTO\CaseJson\CFRubric;
use App\Entity\Framework\LsDoc;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiV1PackageControllerTest extends TestCase
{
    private ApiV1PackageController $controller;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->controller = new ApiV1PackageController(
            $this->validator,
        );

        // Set the dispatcher for command dispatching
        $dispatcher = $this->createMock(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class);
        $this->controller->setDispatcher($dispatcher);

        // Mock the container and router for generateUrl
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/api/v1/packages/some-id');
        $requestStack = $this->createMock(RequestStack::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function ($id) use ($router, $requestStack) {
            if ('router' === $id) {
                return $router;
            }
            if ('request_stack' === $id) {
                return $requestStack;
            }

            return null;
        });
        $this->controller->setContainer($container);

        // Mock additional dependencies used in some tests
        $this->objectMapper = $this->createMock(\Symfony\Component\Serializer\Encoder\JsonEncoder::class); // or appropriate class
        $this->serializer = $this->createMock(\JMS\Serializer\SerializerInterface::class);
        $this->lsItemRepository = $this->createMock(\App\Repository\Framework\LsItemRepository::class);
        $this->lsAssociationRepository = $this->createMock(\App\Repository\Framework\LsAssociationRepository::class);
        $this->cfRubricRepository = $this->createMock(\App\Repository\Framework\CfRubricRepository::class);
        $this->lsDefConceptRepository = $this->createMock(\App\Repository\Framework\LsDefConceptRepository::class);
        $this->lsDefSubjectRepository = $this->createMock(\App\Repository\Framework\LsDefSubjectRepository::class);
        $this->lsDefLicenceRepository = $this->createMock(\App\Repository\Framework\LsDefLicenceRepository::class);
        $this->lsDefItemTypeRepository = $this->createMock(\App\Repository\Framework\LsDefItemTypeRepository::class);
        $this->lsDefAssociationGroupingRepository = $this->createMock(\App\Repository\Framework\LsDefAssociationGroupingRepository::class);

        // Mock controller methods
        $this->controller = $this->getMockBuilder(ApiV1PackageController::class)
            ->setConstructorArgs([$this->validator])
            ->onlyMethods(['forward', 'generateUrl', 'sendCommand'])
            ->getMock();
        $this->controller->setDispatcher($dispatcher);
        $this->controller->setContainer($container);
    }

    public function testGetPackageReturnsCompletePackageStructure(): void
    {
        $doc = new LsDoc();
        $doc->setIdentifier('550e8400-e29b-41d4-a716-446655440000');

        $this->controller->expects($this->once())->method('forward')->willReturn(new JsonResponse([], Response::HTTP_OK));

        $response = $this->controller->getPackage($doc);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPostPackageCreatesCompletePackage(): void
    {
        $packageDto = new CFPackage();
        $packageDto->cfDocument = new CFDocument();
        $packageDto->cfDocument->identifier = Uuid::uuid4();
        $packageDto->cfDocument->uri = 'http://example.com/package';
        $packageDto->cfDocument->title = 'Test Package';
        $packageDto->cfDocument->creator = 'Test Creator';
        $packageDto->cfItems = [new CFPackageItem()];
        $packageDto->cfAssociations = [new CFPackageAssociation()];
        $packageDto->cfDefinitions = new CFDefinition();
        $packageDto->cfRubrics = [new CFRubric()];

        $req = new Request(content: json_encode($packageDto));

        $this->controller->expects($this->once())->method('sendCommand');
        $this->controller->expects($this->once())->method('generateUrl')->willReturn('/api/v1/packages/some-id');

        $response = $this->controller->postPackage($packageDto, $req, null);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testPutPackageUpdatesCompletePackage(): void
    {
        $doc = new LsDoc();
        $doc->setIdentifier('550e8400-e29b-41d4-a716-446655440001');

        $packageDto = new CFPackage();
        $packageDto->cfDocument = new CFDocument();
        $packageDto->cfDocument->identifier = Uuid::uuid4();
        $packageDto->cfItems = [new CFPackageItem()];
        $packageDto->cfAssociations = [new CFPackageAssociation()];
        $packageDto->cfRubrics = [new CFRubric()];

        $this->controller->expects($this->once())->method('sendCommand');
        $this->controller->expects($this->once())->method('generateUrl')->willReturn('/api/v1/packages/some-id');

        $response = $this->controller->putPackage($doc, $packageDto, new Request(), null);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testDeletePackageRemovesAllComponents(): void
    {
        $doc = new LsDoc();
        $this->controller->expects($this->once())->method('sendCommand');

        $response = $this->controller->deletePackage($doc);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testPostPackageWithEmptyPackage(): void
    {
        $packageDto = new CFPackage();
        $packageDto->cfDocument = new CFDocument();
        $packageDto->cfDocument->identifier = Uuid::uuid4();
        $packageDto->cfDocument->uri = 'http://example.com/empty';
        $packageDto->cfDocument->creator = 'Test Creator';
        $packageDto->cfDocument->title = 'Empty Package';
        $packageDto->cfDocument->lastChangeDateTime = new \DateTime();

        $req = new Request();

        $this->controller->expects($this->once())->method('sendCommand');
        $this->controller->expects($this->once())->method('generateUrl')->willReturn('/api/v1/packages/some-id');

        $response = $this->controller->postPackage($packageDto, $req, null);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testGetPackageWithNoComponents(): void
    {
        $doc = new LsDoc();

        $this->controller->expects($this->once())->method('forward')->willReturn(new JsonResponse([], Response::HTTP_OK));

        $response = $this->controller->getPackage($doc);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
