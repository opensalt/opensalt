<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddAssociationCommand;
use App\Command\Framework\AddDocumentCommand;
use App\Command\Framework\AddItemCommand;
use App\Command\Framework\DeleteAssociationCommand;
use App\Command\Framework\DeleteDocumentCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\UpdateDocumentCommand;
use App\Command\Framework\UpdateItemCommand;
use App\DTO\Api\V1\AssociationDto;
use App\DTO\Api\V1\DefinitionDto;
use App\DTO\Api\V1\DocumentDto;
use App\DTO\Api\V1\DocumentFilterDto;
use App\DTO\Api\V1\DocumentListResponseDto;
use App\DTO\Api\V1\DocumentPaginationDto;
use App\DTO\Api\V1\ItemDto;
use App\DTO\Api\V1\PackageDto;
use App\DTO\Api\V1\RubricDto;
use App\Entity\Framework\LsDoc;
use App\Repository\Framework\CfRubricRepository;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDefAssociationGroupingRepository;
use App\Repository\Framework\LsDefConceptRepository;
use App\Repository\Framework\LsDefItemTypeRepository;
use App\Repository\Framework\LsDefLicenceRepository;
use App\Repository\Framework\LsDefSubjectRepository;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Security(name: 'Bearer')]
#[OA\Response(
    response: 401,
    description: 'The token is not valid',
)]
#[OA\Response(
    response: 403,
    description: 'The token does not have access to the item',
)]
#[OA\Response(
    response: 404,
    description: 'The package cannot be found',
)]
#[OA\Tag('Package', description: 'Operations on CASE v1.1 CF Packages')]
class ApiV1PackageController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ObjectMapperInterface $objectMapper,
        private readonly EntityManagerInterface $entityManager,
        private readonly LsDocRepository $lsDocRepository,
        private readonly LsItemRepository $lsItemRepository,
        private readonly LsAssociationRepository $lsAssociationRepository,
        private readonly CfRubricRepository $cfRubricRepository,
        private readonly LsDefConceptRepository $lsDefConceptRepository,
        private readonly LsDefSubjectRepository $lsDefSubjectRepository,
        private readonly LsDefLicenceRepository $lsDefLicenceRepository,
        private readonly LsDefItemTypeRepository $lsDefItemTypeRepository,
        private readonly LsDefAssociationGroupingRepository $lsDefAssociationGroupingRepository,
    ) {
    }

    #[Route('/api/v1/packages', methods: ['GET'])]
    #[OA\Get(
        operationId: 'api_v1_package_index',
        description: 'Get a list of packages with pagination and filtering',
        summary: 'List packages',
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'List of packages',
        content: new Model(type: DocumentListResponseDto::class),
    )]
    public function index(
        #[MapQueryString(key: 'page')] DocumentPaginationDto $page,
        #[MapQueryString(key: 'filter')] DocumentFilterDto $filter,
    ): Response {
        // Get packages with pagination and filtering
        $result = $this->lsDocRepository->findDocumentsWithPagination($page, $filter);

        return new JsonResponse($this->serializer->serialize($result, 'json', []), json: true);
    }

    #[Route('/api/v1/packages/{documentIdentifier}', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'doc')]
    #[OA\Get(
        operationId: 'api_v1_package_get',
        description: 'Get a single package with full structure',
        summary: 'Get framework package',
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The complete package structure',
        content: new Model(type: PackageDto::class, groups: ['view']),
    )]
    public function getPackage(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        // Build complete package structure
        $packageDto = new PackageDto();

        // Get document
        $packageDto->CFDocument = $this->serializer->deserialize(
            $this->serializer->serialize($doc, 'json', ['groups' => ['view']]),
            DocumentDto::class,
            'json'
        );

        // Get items for this document
        $items = $this->lsItemRepository->findBy(['lsDoc' => $doc]);
        $packageDto->CFItems = array_map(function ($item) {
            return $this->serializer->deserialize(
                $this->serializer->serialize($item, 'json', ['groups' => ['view']]),
                ItemDto::class,
                'json'
            );
        }, $items);

        // Get associations
        $associations = $this->lsAssociationRepository->findBy(['lsDoc' => $doc]);
        $packageDto->CFAssociations = array_map(function ($assoc) {
            return $this->serializer->deserialize(
                $this->serializer->serialize($assoc, 'json', ['groups' => ['view']]),
                AssociationDto::class,
                'json'
            );
        }, $associations);

        // Get definitions (concepts, subjects, licences, item types, association groupings)
        $definitions = new DefinitionDto();
        $definitions->CFConcepts = $this->lsDefConceptRepository->findAll();
        $definitions->CFSubjects = $this->lsDefSubjectRepository->findAll();
        $definitions->CFLicenses = $this->lsDefLicenceRepository->findAll();
        $definitions->CFItemTypes = $this->lsDefItemTypeRepository->findAll();
        $definitions->CFAssociationGroupings = $this->lsDefAssociationGroupingRepository->findAll();
        $packageDto->CFDefinitions = $definitions;

        // Get rubrics (rubrics are global, not document-specific)
        $rubrics = $this->cfRubricRepository->findAll();
        $packageDto->CFRubrics = array_map(function ($rubric) {
            return $this->serializer->deserialize(
                $this->serializer->serialize($rubric, 'json', ['groups' => ['view']]),
                RubricDto::class,
                'json'
            );
        }, $rubrics);

        return new JsonResponse(
            $this->serializer->serialize($packageDto, 'json', ['groups' => ['view']]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/v1/packages', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    #[OA\Post(
        operationId: 'api_v1_package_post',
        description: 'Create a new complete package with all components',
        summary: 'Create package',
    )]
    #[OA\RequestBody(content: new Model(type: PackageDto::class, groups: ['create']))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Package created successfully',
        content: new Model(type: PackageDto::class, groups: ['view'])
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'There is an error in the package payload',
    )]
    public function postPackage(
        #[MapRequestPayload(validationGroups: ['create'])] PackageDto $packageDto,
    ): Response {
        // Create document first
        $lsDoc = new LsDoc();
        if ($packageDto->CFDocument) {
            $documentDto = $packageDto->CFDocument;
            $lsDoc->setIdentifier($documentDto->identifier ?? Uuid::uuid4()->toString());
            $documentDto->identifier = Uuid::fromString($lsDoc->getIdentifier());
            $documentDto->uri ??= $lsDoc->getUri();
            $this->objectMapper->map($documentDto, $lsDoc);
        }

        $command = new AddDocumentCommand($lsDoc);
        $this->sendCommand($command);

        // Create items
        if ($packageDto->CFItems) {
            foreach ($packageDto->CFItems as $itemDto) {
                $item = new \App\Entity\Framework\LsItem();
                $item->setLsDoc($lsDoc);
                $item->setIdentifier($itemDto->identifier ?? Uuid::uuid4()->toString());
                $itemDto->identifier = Uuid::fromString($item->getIdentifier());
                $itemDto->uri ??= $item->getUri();
                $this->objectMapper->map($itemDto, $item);
                $this->sendCommand(new AddItemCommand($item, $lsDoc));
            }
        }

        // Create associations
        if ($packageDto->CFAssociations) {
            foreach ($packageDto->CFAssociations as $assocDto) {
                $assoc = new \App\Entity\Framework\LsAssociation();
                $assoc->setLsDoc($lsDoc);
                // Set uri for originNodeURI
                if ($assocDto->originNodeURI && !$assocDto->originNodeURI->uri) {
                    $originItem = $this->lsItemRepository->findOneBy(['identifier' => $assocDto->originNodeURI->identifier->toString()]);
                    if ($originItem) {
                        $assocDto->originNodeURI->uri = $originItem->getUri();
                    }
                }
                // Set uri for destinationNodeURI
                if ($assocDto->destinationNodeURI && !$assocDto->destinationNodeURI->uri) {
                    $destinationItem = $this->lsItemRepository->findOneBy(['identifier' => $assocDto->destinationNodeURI->identifier->toString()]);
                    if ($destinationItem) {
                        $assocDto->destinationNodeURI->uri = $destinationItem->getUri();
                    }
                }
                $this->objectMapper->map($assocDto, $assoc);
                $this->sendCommand(new AddAssociationCommand($assoc));
            }
        }

        // Handle definitions (these are global, not document-specific)
        if ($packageDto->CFDefinitions) {
            $this->handleDefinitions($packageDto->CFDefinitions);
        }

        // Create rubrics
        if ($packageDto->CFRubrics) {
            foreach ($packageDto->CFRubrics as $rubricDto) {
                $rubric = new \App\Entity\Framework\CfRubric();
                $rubric->setIdentifier($rubricDto->identifier ?? Uuid::uuid4()->toString());
                $rubricDto->identifier = Uuid::fromString($rubric->getIdentifier());
                $rubricDto->uri ??= $rubric->getUri();
                $this->objectMapper->map($rubricDto, $rubric);
                $this->entityManager->persist($rubric);
            }
            $this->entityManager->flush();
        }

        // Return the complete package
        $response = $this->getPackage($lsDoc);

        return new JsonResponse($response->getContent(), Response::HTTP_CREATED, $response->headers->all(), true);
    }

    private function handleDefinitions(DefinitionDto $definitions): void
    {
        // Handle concepts
        if ($definitions->CFConcepts) {
            foreach ($definitions->CFConcepts as $concept) {
                $entity = new \App\Entity\Framework\LsDefConcept();
                $this->objectMapper->map($concept, $entity);
                $this->entityManager->persist($entity);
            }
        }

        // Handle subjects
        if ($definitions->CFSubjects) {
            foreach ($definitions->CFSubjects as $subject) {
                $entity = new \App\Entity\Framework\LsDefSubject();
                $this->objectMapper->map($subject, $entity);
                $this->entityManager->persist($entity);
            }
        }

        // Handle licences
        if ($definitions->CFLicenses) {
            foreach ($definitions->CFLicenses as $licence) {
                $entity = new \App\Entity\Framework\LsDefLicence();
                $this->objectMapper->map($licence, $entity);
                $this->entityManager->persist($entity);
            }
        }

        // Handle item types
        if ($definitions->CFItemTypes) {
            foreach ($definitions->CFItemTypes as $itemType) {
                $entity = new \App\Entity\Framework\LsDefItemType();
                $this->objectMapper->map($itemType, $entity);
                $this->entityManager->persist($entity);
            }
        }

        // Handle association groupings
        if ($definitions->CFAssociationGroupings) {
            foreach ($definitions->CFAssociationGroupings as $grouping) {
                $entity = new \App\Entity\Framework\LsDefAssociationGrouping();
                $this->objectMapper->map($grouping, $entity);
                $this->entityManager->persist($entity);
            }
        }

        $this->entityManager->flush();
    }

    #[Route('/api/v1/packages/{documentIdentifier}', methods: ['PUT'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Put(
        operationId: 'api_v1_package_put',
        description: 'Replace an existing complete package',
        summary: 'Replace package',
    )]
    #[OA\RequestBody(content: new Model(type: PackageDto::class, groups: ['update']))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Package updated successfully',
        content: new Model(type: PackageDto::class, groups: ['view'])
    )]
    public function putPackage(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
        #[MapRequestPayload(validationGroups: ['update'])] PackageDto $packageDto,
    ): Response {
        // Update document
        if ($packageDto->CFDocument) {
            $documentDto = $packageDto->CFDocument;
            $documentDto->identifier = Uuid::fromString($doc->getIdentifier());
            $documentDto->uri = $doc->getUri();
            $this->objectMapper->map($documentDto, $doc);
            $this->sendCommand(new UpdateDocumentCommand($doc));
        }

        // Update/create items
        if ($packageDto->CFItems) {
            // First, get existing items
            $existingItems = $this->lsItemRepository->findBy(['lsDoc' => $doc]);
            $existingItemIds = array_map(fn ($item) => $item->getIdentifier(), $existingItems);

            foreach ($packageDto->CFItems as $itemDto) {
                $itemId = $itemDto->identifier?->toString();
                if (in_array($itemId, $existingItemIds)) {
                    // Update existing item
                    $item = $this->lsItemRepository->findOneBy(['identifier' => $itemId]);
                    $this->objectMapper->map($itemDto, $item);
                    $this->sendCommand(new UpdateItemCommand($item));
                } else {
                    // Create new item
                    $item = new \App\Entity\Framework\LsItem();
                    $item->setLsDoc($doc);
                    $item->setIdentifier($itemDto->identifier ?? Uuid::uuid4()->toString());
                    $itemDto->identifier = Uuid::fromString($item->getIdentifier());
                    $itemDto->uri ??= $item->getUri();
                    $this->objectMapper->map($itemDto, $item);
                    $this->sendCommand(new AddItemCommand($item, $doc));
                }
            }
        }

        // Update associations (similar logic)
        if ($packageDto->CFAssociations) {
            $existingAssocs = $this->lsAssociationRepository->findBy(['lsDoc' => $doc]);
            foreach ($packageDto->CFAssociations as $assocDto) {
                // For simplicity, recreate all associations
                $assoc = new \App\Entity\Framework\LsAssociation();
                $assoc->setLsDoc($doc);
                // Set uri for originNodeURI
                if ($assocDto->originNodeURI && !$assocDto->originNodeURI->uri) {
                    $originItem = $this->lsItemRepository->findOneBy(['identifier' => $assocDto->originNodeURI->identifier->toString()]);
                    if ($originItem) {
                        $assocDto->originNodeURI->uri = $originItem->getUri();
                    }
                }
                // Set uri for destinationNodeURI
                if ($assocDto->destinationNodeURI && !$assocDto->destinationNodeURI->uri) {
                    $destinationItem = $this->lsItemRepository->findOneBy(['identifier' => $assocDto->destinationNodeURI->identifier->toString()]);
                    if ($destinationItem) {
                        $assocDto->destinationNodeURI->uri = $destinationItem->getUri();
                    }
                }
                $this->objectMapper->map($assocDto, $assoc);
                $this->sendCommand(new AddAssociationCommand($assoc));
            }
        }

        // Handle definitions
        if ($packageDto->CFDefinitions) {
            $this->handleDefinitions($packageDto->CFDefinitions);
        }

        // Update rubrics (rubrics are global, not document-specific)
        if ($packageDto->CFRubrics) {
            $existingRubrics = $this->cfRubricRepository->findAll();
            $existingRubricIds = array_map(fn ($rubric) => $rubric->getIdentifier(), $existingRubrics);

            foreach ($packageDto->CFRubrics as $rubricDto) {
                $rubricId = $rubricDto->identifier?->toString();
                if (in_array($rubricId, $existingRubricIds)) {
                    // Update existing rubric
                    $rubric = $this->cfRubricRepository->findOneBy(['identifier' => $rubricId]);
                    $this->objectMapper->map($rubricDto, $rubric);
                } else {
                    // Create new rubric
                    $rubric = new \App\Entity\Framework\CfRubric();
                    $rubric->setIdentifier($rubricDto->identifier ?? Uuid::uuid4()->toString());
                    $rubricDto->identifier = Uuid::fromString($rubric->getIdentifier());
                    $rubricDto->uri ??= $rubric->getUri();
                    $this->objectMapper->map($rubricDto, $rubric);
                    $this->entityManager->persist($rubric);
                }
            }
            $this->entityManager->flush();
        }

        // Return the updated package
        return $this->getPackage($doc);
    }

    #[Route('/api/v1/packages/{documentIdentifier}', methods: ['DELETE'])]
    #[IsGranted(Permission::FRAMEWORK_DELETE, 'doc')]
    #[OA\Delete(
        operationId: 'api_v1_package_delete',
        description: 'Delete a complete package and all related entities',
        summary: 'Delete package',
    )]
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'The package and all related entities have been deleted',
    )]
    public function deletePackage(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        // Delete items
        $items = $this->lsItemRepository->findBy(['lsDoc' => $doc]);
        foreach ($items as $item) {
            $this->sendCommand(new DeleteItemCommand($item));
        }

        // Delete associations
        $associations = $this->lsAssociationRepository->findBy(['lsDoc' => $doc]);
        foreach ($associations as $assoc) {
            $this->sendCommand(new DeleteAssociationCommand($assoc));
        }

        // Delete rubrics (rubrics are global, not document-specific - do not delete them)
        // $rubrics = $this->cfRubricRepository->findBy(['lsDoc' => $doc]);
        // foreach ($rubrics as $rubric) {
        //     $this->entityManager->remove($rubric);
        // }
        // $this->entityManager->flush();

        // Finally delete the document
        $command = new DeleteDocumentCommand($doc);
        $this->sendCommand($command);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
