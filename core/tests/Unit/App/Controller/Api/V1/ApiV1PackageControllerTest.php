<?php

declare(strict_types=1);

namespace Tests\Unit\App\Controller\Api\V1;

use App\Controller\Api\V1\ApiV1PackageController;
use App\DTO\Api\V1\PackageDto;
use App\DTO\Api\V1\DocumentDto;
use App\DTO\Api\V1\ItemDto;
use App\DTO\Api\V1\AssociationDto;
use App\DTO\Api\V1\DefinitionDto;
use App\DTO\Api\V1\RubricDto;
use App\DTO\Api\V1\DocumentFilterDto;
use App\DTO\Api\V1\DocumentPaginationDto;
use App\DTO\Api\V1\DocumentListResponseDto;
use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\CfRubricRepository;
use App\Repository\Framework\LsDefConceptRepository;
use App\Repository\Framework\LsDefSubjectRepository;
use App\Repository\Framework\LsDefLicenceRepository;
use App\Repository\Framework\LsDefItemTypeRepository;
use App\Repository\Framework\LsDefAssociationGroupingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Ramsey\Uuid\Uuid;

class ApiV1PackageControllerTest extends TestCase
{
    private ApiV1PackageController $controller;
    private SerializerInterface $serializer;
    private ObjectMapperInterface $objectMapper;
    private EntityManagerInterface $entityManager;
    private LsDocRepository $lsDocRepository;
    private LsItemRepository $lsItemRepository;
    private LsAssociationRepository $lsAssociationRepository;
    private CfRubricRepository $cfRubricRepository;
    private LsDefConceptRepository $lsDefConceptRepository;
    private LsDefSubjectRepository $lsDefSubjectRepository;
    private LsDefLicenceRepository $lsDefLicenceRepository;
    private LsDefItemTypeRepository $lsDefItemTypeRepository;
    private LsDefAssociationGroupingRepository $lsDefAssociationGroupingRepository;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->objectMapper = $this->createMock(ObjectMapperInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->lsDocRepository = $this->createMock(LsDocRepository::class);
        $this->lsItemRepository = $this->createMock(LsItemRepository::class);
        $this->lsAssociationRepository = $this->createMock(LsAssociationRepository::class);
        $this->cfRubricRepository = $this->createMock(CfRubricRepository::class);
        $this->lsDefConceptRepository = $this->createMock(LsDefConceptRepository::class);
        $this->lsDefSubjectRepository = $this->createMock(LsDefSubjectRepository::class);
        $this->lsDefLicenceRepository = $this->createMock(LsDefLicenceRepository::class);
        $this->lsDefItemTypeRepository = $this->createMock(LsDefItemTypeRepository::class);
        $this->lsDefAssociationGroupingRepository = $this->createMock(LsDefAssociationGroupingRepository::class);

        $this->controller = new ApiV1PackageController(
            $this->serializer,
            $this->objectMapper,
            $this->entityManager,
            $this->lsDocRepository,
            $this->lsItemRepository,
            $this->lsAssociationRepository,
            $this->cfRubricRepository,
            $this->lsDefConceptRepository,
            $this->lsDefSubjectRepository,
            $this->lsDefLicenceRepository,
            $this->lsDefItemTypeRepository,
            $this->lsDefAssociationGroupingRepository
        );

        // Set the dispatcher for command dispatching
        $this->controller->setDispatcher($this->createMock(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class));
    }

    public function testIndexReturnsJsonResponse(): void
    {
        $pagination = new DocumentPaginationDto();
        $filter = new DocumentFilterDto();
        $paginationResponse = new \App\DTO\Api\V1\DocumentPaginationResponseDto(false, null, 0);
        $responseDto = new DocumentListResponseDto([], $paginationResponse);

        $this->lsDocRepository->expects($this->once())
            ->method('findDocumentsWithPagination')
            ->with($pagination, $filter)
            ->willReturn($responseDto);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($responseDto, 'json', [])
            ->willReturn('{"data":[],"pagination":{"hasNextPage":false,"nextCursor":null,"total":0}}');

        $response = $this->controller->index($pagination, $filter);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPackageReturnsCompletePackageStructure(): void
    {
        $doc = new LsDoc();
        $doc->setIdentifier('550e8400-e29b-41d4-a716-446655440000');

        // Mock items
        $item = new \App\Entity\Framework\LsItem();
        $this->lsItemRepository->expects($this->once())
            ->method('findBy')
            ->with(['lsDoc' => $doc])
            ->willReturn([$item]);

        // Mock associations
        $association = new \App\Entity\Framework\LsAssociation();
        $this->lsAssociationRepository->expects($this->once())
            ->method('findBy')
            ->with(['lsDoc' => $doc])
            ->willReturn([$association]);

        // Mock definitions
        $this->lsDefConceptRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        $this->lsDefSubjectRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        $this->lsDefLicenceRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        $this->lsDefItemTypeRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        $this->lsDefAssociationGroupingRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        // Mock rubrics
        $rubric = new \App\Entity\Framework\CfRubric();
        $this->cfRubricRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$rubric]);

        $this->serializer->expects($this->any())
            ->method('deserialize')
            ->willReturnCallback(function ($data, $class, $format = null) {
                if ($class === DocumentDto::class) {
                    return new DocumentDto();
                } elseif ($class === ItemDto::class) {
                    return new ItemDto();
                } elseif ($class === AssociationDto::class) {
                    return new AssociationDto();
                } elseif ($class === RubricDto::class) {
                    return new RubricDto();
                }
                return null;
            });

        $this->serializer->expects($this->any())
            ->method('serialize')
            ->willReturn('{"CFDocument":{},"CFItems":[],"CFAssociations":[],"CFDefinitions":{},"CFRubrics":[]}');

        $response = $this->controller->getPackage($doc);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPostPackageCreatesCompletePackage(): void
    {
        $packageDto = new PackageDto();
        $packageDto->CFDocument = new DocumentDto();
        $packageDto->CFDocument->title = 'Test Package';
        $packageDto->CFDocument->creator = 'Test Creator';
        $packageDto->CFItems = [new ItemDto()];
        $packageDto->CFAssociations = [new AssociationDto()];
        $packageDto->CFDefinitions = new DefinitionDto();
        $packageDto->CFRubrics = [new RubricDto()];

        $this->objectMapper->expects($this->any())
            ->method('map');

        $this->entityManager->expects($this->any())
            ->method('persist');
        $this->entityManager->expects($this->any())
            ->method('flush');

        $this->serializer->expects($this->any())
            ->method('serialize')
            ->willReturn('{"CFDocument":{},"CFItems":[],"CFAssociations":[],"CFDefinitions":{},"CFRubrics":[]}');

        $response = $this->controller->postPackage($packageDto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testPutPackageUpdatesCompletePackage(): void
    {
        $doc = new LsDoc();
        $doc->setIdentifier('550e8400-e29b-41d4-a716-446655440001');

        $packageDto = new PackageDto();
        $packageDto->CFDocument = new DocumentDto();
        $packageDto->CFItems = [new ItemDto()];
        $packageDto->CFAssociations = [new AssociationDto()];
        $packageDto->CFRubrics = [new RubricDto()];

        // Mock existing items
        $existingItem = new \App\Entity\Framework\LsItem();
        $this->lsItemRepository->expects($this->any())
            ->method('findBy')
            ->with(['lsDoc' => $doc])
            ->willReturn([$existingItem]);

        // Mock existing associations
        $this->lsAssociationRepository->expects($this->any())
            ->method('findBy')
            ->with(['lsDoc' => $doc])
            ->willReturn([]);

        // Mock existing rubrics
        $existingRubric = new \App\Entity\Framework\CfRubric();
        $this->cfRubricRepository->expects($this->any())
            ->method('findBy')
            ->with(['lsDoc' => $doc])
            ->willReturn([$existingRubric]);

        $this->objectMapper->expects($this->any())
            ->method('map');

        $this->entityManager->expects($this->any())
            ->method('flush');

        $this->serializer->expects($this->any())
            ->method('serialize')
            ->willReturn('{"CFDocument":{},"CFItems":[],"CFAssociations":[],"CFDefinitions":{},"CFRubrics":[]}');

        $response = $this->controller->putPackage($doc, $packageDto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeletePackageRemovesAllComponents(): void
    {
        $doc = new LsDoc();

        // Mock items to delete
        $item = new \App\Entity\Framework\LsItem();
        $this->lsItemRepository->expects($this->any())
            ->method('findBy')
            ->with(['lsDoc' => $doc])
            ->willReturn([$item]);

        // Mock associations to delete
        $association = new \App\Entity\Framework\LsAssociation();
        $this->lsAssociationRepository->expects($this->any())
            ->method('findBy')
            ->with(['lsDoc' => $doc])
            ->willReturn([$association]);

        // Mock rubrics to delete
        $rubric = new \App\Entity\Framework\CfRubric();
        $this->cfRubricRepository->expects($this->any())
            ->method('findBy')
            ->with(['lsDoc' => $doc])
            ->willReturn([$rubric]);

        $response = $this->controller->deletePackage($doc);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testPostPackageWithEmptyPackage(): void
    {
        $packageDto = new PackageDto();
        $packageDto->CFDocument = new DocumentDto();
        $packageDto->CFDocument->title = 'Empty Package';

        $this->objectMapper->expects($this->any())
            ->method('map');

        $this->serializer->expects($this->any())
            ->method('serialize')
            ->willReturn('{"CFDocument":{},"CFItems":[],"CFAssociations":[],"CFDefinitions":{},"CFRubrics":[]}');

        $response = $this->controller->postPackage($packageDto);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testGetPackageWithNoComponents(): void
    {
        $doc = new LsDoc();

        $this->lsItemRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);
        $this->lsAssociationRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);
        $this->cfRubricRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $this->lsDefConceptRepository->expects($this->any())->method('findAll')->willReturn([]);
        $this->lsDefSubjectRepository->expects($this->any())->method('findAll')->willReturn([]);
        $this->lsDefLicenceRepository->expects($this->any())->method('findAll')->willReturn([]);
        $this->lsDefItemTypeRepository->expects($this->any())->method('findAll')->willReturn([]);
        $this->lsDefAssociationGroupingRepository->expects($this->any())->method('findAll')->willReturn([]);

        $this->serializer->expects($this->any())
            ->method('deserialize')
            ->willReturn(new DocumentDto());

        $this->serializer->expects($this->any())
            ->method('serialize')
            ->willReturn('{"CFDocument":{},"CFItems":[],"CFAssociations":[],"CFDefinitions":{},"CFRubrics":[]}');

        $response = $this->controller->getPackage($doc);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
