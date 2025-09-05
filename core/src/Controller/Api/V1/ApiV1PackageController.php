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
use App\DTO\Api\V1\CFRubricCriteriaDto;
use App\DTO\Api\V1\CFRubricCriteriaLevelDto;
use App\DTO\Api\V1\DefinitionDto;
use App\DTO\Api\V1\DocumentDto;
use App\DTO\Api\V1\ItemDto;
use App\DTO\Api\V1\PackageDto;
use App\DTO\Api\V1\RubricDto;
use App\Entity\Framework\CfRubric;
use App\Entity\Framework\CfRubricCriterion;
use App\Entity\Framework\CfRubricCriterionLevel;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDefConcept;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\CfRubricCriterionLevelRepository;
use App\Repository\Framework\CfRubricCriterionRepository;
use App\Repository\Framework\CfRubricRepository;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDefAssociationGroupingRepository;
use App\Repository\Framework\LsDefConceptRepository;
use App\Repository\Framework\LsDefItemTypeRepository;
use App\Repository\Framework\LsDefLicenceRepository;
use App\Repository\Framework\LsDefSubjectRepository;
use App\Repository\Framework\LsItemRepository;
use App\Security\Permission;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
        private readonly LsItemRepository $lsItemRepository,
        private readonly LsAssociationRepository $lsAssociationRepository,
        private readonly CfRubricRepository $cfRubricRepository,
        private readonly CfRubricCriterionRepository $cfRubricCriterionRepository,
        private readonly CfRubricCriterionLevelRepository $cfRubricCriterionLevelRepository,
        private readonly LsDefConceptRepository $lsDefConceptRepository,
        private readonly LsDefSubjectRepository $lsDefSubjectRepository,
        private readonly LsDefLicenceRepository $lsDefLicenceRepository,
        private readonly LsDefItemTypeRepository $lsDefItemTypeRepository,
        private readonly LsDefAssociationGroupingRepository $lsDefAssociationGroupingRepository,
    ) {
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

        $packageDto->CFItems = $this->buildItems($doc);
        $packageDto->CFAssociations = $this->buildAssociations($doc);
        $packageDto->CFDefinitions = $this->buildDefinitions();
        $packageDto->CFRubrics = $this->buildRubrics();

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
                $item = new LsItem();
                $item->setLsDoc($lsDoc);
                $item->setIdentifier($itemDto->identifier ?? Uuid::uuid4()->toString());
                $itemDto->identifier = Uuid::fromString($item->getIdentifier());
                $itemDto->uri ??= $item->getUri();
                $this->objectMapper->map($itemDto, $item);
                $this->sendCommand(new AddItemCommand($item, $lsDoc));
            }
        }

        // Create associations
        $this->updateAssociations($lsDoc, $packageDto);

        // Handle definitions (these are global, not document-specific)
        if ($packageDto->CFDefinitions) {
            $this->handleDefinitions($packageDto->CFDefinitions);
        }

        // Create rubrics
        if ($packageDto->CFRubrics) {
            foreach ($packageDto->CFRubrics as $rubricDto) {
                $rubric = new CfRubric();
                $rubric->setIdentifier($rubricDto->identifier ?? Uuid::uuid4()->toString());
                $rubricDto->identifier = Uuid::fromString($rubric->getIdentifier());
                $rubricDto->uri ??= $rubric->getUri();
                $rubric->setTitle($rubricDto->title ?? null);
                $rubric->setDescription($rubricDto->description ?? null);

                // Create criteria
                if ($rubricDto->CFRubricCriteria) {
                    foreach ($rubricDto->CFRubricCriteria as $criterionDto) {
                        $criterion = new CfRubricCriterion($rubric);
                        $criterion->setIdentifier($criterionDto->identifier ?? Uuid::uuid4()->toString());
                        $criterionDto->identifier = Uuid::fromString($criterion->getIdentifier());
                        $criterionDto->uri ??= $criterion->getUri();

                        // Set item if CFItemURI is provided
                        if ($criterionDto->CFItemURI) {
                            $item = $this->lsItemRepository->findOneBy(['identifier' => $criterionDto->CFItemURI]);
                            if ($item) {
                                $criterion->setItem($item);
                            }
                        }

                        $criterion->setCategory($criterionDto->category);
                        $criterion->setDescription($criterionDto->description);
                        $criterion->setWeight($criterionDto->weight);
                        $criterion->setPosition($criterionDto->position);

                        // Create levels
                        if ($criterionDto->CFRubricCriteriaLevels) {
                            foreach ($criterionDto->CFRubricCriteriaLevels as $levelDto) {
                                $level = new CfRubricCriterionLevel($criterion);
                                $level->setIdentifier($levelDto->identifier ?? Uuid::uuid4()->toString());
                                $levelDto->identifier = Uuid::fromString($level->getIdentifier());
                                $levelDto->uri ??= $level->getUri();
                                $level->setDescription($levelDto->description);
                                $level->setQuality($levelDto->quality);
                                $level->setScore($levelDto->score);
                                $level->setFeedback($levelDto->feedback);
                                $level->setPosition($levelDto->position);
                                $this->entityManager->persist($level);
                            }
                        }

                        $this->entityManager->persist($criterion);
                    }
                }

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
                $entity = new LsDefConcept();
                $this->objectMapper->map($concept, $entity);
                $this->entityManager->persist($entity);
            }
        }

        // Handle subjects
        if ($definitions->CFSubjects) {
            foreach ($definitions->CFSubjects as $subject) {
                $entity = new LsDefSubject();
                $this->objectMapper->map($subject, $entity);
                $this->entityManager->persist($entity);
            }
        }

        // Handle licences
        if ($definitions->CFLicenses) {
            foreach ($definitions->CFLicenses as $licence) {
                $entity = new LsDefLicence();
                $this->objectMapper->map($licence, $entity);
                $this->entityManager->persist($entity);
            }
        }

        // Handle item types
        if ($definitions->CFItemTypes) {
            foreach ($definitions->CFItemTypes as $itemType) {
                $entity = new LsDefItemType();
                $this->objectMapper->map($itemType, $entity);
                $this->entityManager->persist($entity);
            }
        }

        // Handle association groupings
        if ($definitions->CFAssociationGroupings) {
            foreach ($definitions->CFAssociationGroupings as $grouping) {
                $entity = new LsDefAssociationGrouping();
                $this->objectMapper->map($grouping, $entity);
                $this->entityManager->persist($entity);
            }
        }

        $this->entityManager->flush();
    }

    private function updateDocument(LsDoc $doc, PackageDto $packageDto): void
    {
        if (null === $packageDto->CFDocument) {
            return;
        }

        $documentDto = $packageDto->CFDocument;
        $documentDto->identifier = Uuid::fromString($doc->getIdentifier());
        $documentDto->uri = $doc->getUri();
        $this->objectMapper->map($documentDto, $doc);
        $this->sendCommand(new UpdateDocumentCommand($doc));
    }

    private function updateItems(LsDoc $doc, PackageDto $packageDto): void
    {
        if (null === $packageDto->CFItems) {
            return;
        }

        $existingItems = $this->lsItemRepository->findBy(['lsDoc' => $doc]);
        $existingItemIds = array_map(fn ($item) => $item->getIdentifier(), $existingItems);

        foreach ($packageDto->CFItems as $itemDto) {
            $itemId = $itemDto->identifier?->toString();
            if (in_array($itemId, $existingItemIds)) {
                $item = $this->lsItemRepository->findOneBy(['identifier' => $itemId]);
                $this->objectMapper->map($itemDto, $item);
                $this->sendCommand(new UpdateItemCommand($item));
            } else {
                $item = new LsItem();
                $item->setLsDoc($doc);
                $item->setIdentifier($itemDto->identifier ?? Uuid::uuid4()->toString());
                $itemDto->identifier = Uuid::fromString($item->getIdentifier());
                $itemDto->uri ??= $item->getUri();
                $this->objectMapper->map($itemDto, $item);
                $this->sendCommand(new AddItemCommand($item, $doc));
            }
        }
    }

    private function updateAssociations(LsDoc $doc, PackageDto $packageDto): void
    {
        foreach ($packageDto->CFAssociations ?? [] as $assocDto) {
            $assoc = new LsAssociation();
            $assoc->setLsDoc($doc);
            if ($assocDto->originNodeURI && !$assocDto->originNodeURI->uri) {
                $originItem = $this->lsItemRepository->findOneBy(['identifier' => $assocDto->originNodeURI->identifier->toString()]);
                if ($originItem) {
                    $assocDto->originNodeURI->uri = $originItem->getUri();
                }
            }
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

    private function updateRubrics(PackageDto $packageDto): void
    {
        if (null === $packageDto->CFRubrics) {
            return;
        }

        /** @var CfRubric[] $existingRubrics */
        $existingRubrics = $this->cfRubricRepository->findAll();
        $existingRubricIds = array_map(fn ($rubric): string => $rubric->getIdentifier(), $existingRubrics);

        foreach ($packageDto->CFRubrics as $rubricDto) {
            $rubricId = $rubricDto->identifier?->toString();
            if (in_array($rubricId, $existingRubricIds)) {
                $rubric = $this->cfRubricRepository->findOneBy(['identifier' => $rubricId]);
                $rubric->setTitle($rubricDto->title ?? null);
                $rubric->setDescription($rubricDto->description ?? null);
            } else {
                $rubric = new CfRubric();
                $rubric->setIdentifier($rubricDto->identifier ?? Uuid::uuid4()->toString());
                $rubricDto->identifier = Uuid::fromString($rubric->getIdentifier());
                $rubricDto->uri ??= $rubric->getUri();
                $rubric->setTitle($rubricDto->title ?? null);
                $rubric->setDescription($rubricDto->description ?? null);
                $this->entityManager->persist($rubric);
            }

            $this->updateRubricCriteria($rubric, $rubricDto);
        }
        $this->entityManager->flush();
    }

    private function updateRubricCriteria(CfRubric $rubric, RubricDto $rubricDto): void
    {
        if (null === $rubricDto->CFRubricCriteria) {
            return;
        }

        $existingCriteria = $rubric->getCriteria();
        $existingCriterionIds = array_map(fn ($criterion) => $criterion->getIdentifier(), $existingCriteria->toArray());

        foreach ($rubricDto->CFRubricCriteria as $criterionDto) {
            $criterionId = $criterionDto->identifier?->toString();
            if (in_array($criterionId, $existingCriterionIds)) {
                $criterion = $this->cfRubricCriterionRepository->findOneBy(['identifier' => $criterionId]);
                $criterion->setCategory($criterionDto->category);
                $criterion->setDescription($criterionDto->description);
                $criterion->setWeight($criterionDto->weight);
                $criterion->setPosition($criterionDto->position);
            } else {
                $criterion = new CfRubricCriterion($rubric);
                $criterion->setIdentifier($criterionDto->identifier ?? Uuid::uuid4()->toString());
                $criterionDto->identifier = Uuid::fromString($criterion->getIdentifier());
                $criterionDto->uri ??= $criterion->getUri();

                if ($criterionDto->CFItemURI) {
                    $item = $this->lsItemRepository->findOneBy(['identifier' => $criterionDto->CFItemURI]);
                    if ($item) {
                        $criterion->setItem($item);
                    }
                }

                $criterion->setCategory($criterionDto->category);
                $criterion->setDescription($criterionDto->description);
                $criterion->setWeight($criterionDto->weight);
                $criterion->setPosition($criterionDto->position);
                $this->entityManager->persist($criterion);
            }

            $this->updateRubricLevels($criterion, $criterionDto);
        }
    }

    private function updateRubricLevels(CfRubricCriterion $criterion, CFRubricCriteriaDto $criterionDto): void
    {
        if (null === $criterionDto->CFRubricCriteriaLevels) {
            return;
        }

        $existingLevels = $criterion->getLevels();
        $existingLevelIds = array_map(fn ($level) => $level->getIdentifier(), $existingLevels->toArray());

        foreach ($criterionDto->CFRubricCriteriaLevels as $levelDto) {
            $levelId = $levelDto->identifier?->toString();
            if (in_array($levelId, $existingLevelIds)) {
                $level = $this->cfRubricCriterionLevelRepository->findOneBy(['identifier' => $levelId]);
                $level->setDescription($levelDto->description);
                $level->setQuality($levelDto->quality);
                $level->setScore($levelDto->score);
                $level->setFeedback($levelDto->feedback);
                $level->setPosition($levelDto->position);
            } else {
                $level = new CfRubricCriterionLevel($criterion);
                $level->setIdentifier($levelDto->identifier ?? Uuid::uuid4()->toString());
                $levelDto->identifier = Uuid::fromString($level->getIdentifier());
                $levelDto->uri ??= $level->getUri();
                $level->setDescription($levelDto->description);
                $level->setQuality($levelDto->quality);
                $level->setScore($levelDto->score);
                $level->setFeedback($levelDto->feedback);
                $level->setPosition($levelDto->position);
                $this->entityManager->persist($level);
            }
        }
    }

    private function buildItems(LsDoc $doc): array
    {
        $items = $this->lsItemRepository->findBy(['lsDoc' => $doc]);

        return array_map(function ($item) {
            return $this->serializer->deserialize(
                $this->serializer->serialize($item, 'json', ['groups' => ['view']]),
                ItemDto::class,
                'json'
            );
        }, $items);
    }

    private function buildAssociations(LsDoc $doc): array
    {
        $associations = $this->lsAssociationRepository->findBy(['lsDoc' => $doc]);

        return array_map(function ($assoc) {
            return $this->serializer->deserialize(
                $this->serializer->serialize($assoc, 'json', ['groups' => ['view']]),
                AssociationDto::class,
                'json'
            );
        }, $associations);
    }

    private function buildDefinitions(): DefinitionDto
    {
        $definitions = new DefinitionDto();
        $definitions->CFConcepts = $this->lsDefConceptRepository->findAll();
        $definitions->CFSubjects = $this->lsDefSubjectRepository->findAll();
        $definitions->CFLicenses = $this->lsDefLicenceRepository->findAll();
        $definitions->CFItemTypes = $this->lsDefItemTypeRepository->findAll();
        $definitions->CFAssociationGroupings = $this->lsDefAssociationGroupingRepository->findAll();

        return $definitions;
    }

    private function buildRubrics(): array
    {
        $rubrics = $this->cfRubricRepository->findAll();

        return array_map(function ($rubric) {
            $rubricDto = $this->serializer->deserialize(
                $this->serializer->serialize($rubric, 'json', ['groups' => ['view']]),
                RubricDto::class,
                'json'
            );

            $rubricDto->CFRubricCriteria = $this->buildCriteria($rubric->getCriteria());

            return $rubricDto;
        }, $rubrics);
    }

    /**
     * @param Collection<array-key, CfRubricCriterion> $criteria
     *
     * @return CFRubricCriteriaDto[]
     */
    private function buildCriteria(Collection $criteria): array
    {
        return array_map(function ($criterion) {
            $criterionDto = new CFRubricCriteriaDto();
            $criterionDto->identifier = Uuid::fromString($criterion->getIdentifier());
            $criterionDto->uri = $criterion->getUri();
            $criterionDto->category = $criterion->getCategory();
            $criterionDto->description = $criterion->getDescription();
            $criterionDto->CFItemURI = $criterion->getItem()?->getIdentifier();
            $criterionDto->weight = $criterion->getWeight();
            $criterionDto->position = $criterion->getPosition();
            $criterionDto->lastChangeDateTime = $criterion->getChangedAt();
            $criterionDto->CFRubricCriteriaLevels = $this->buildLevels($criterion->getLevels());

            return $criterionDto;
        }, $criteria->toArray());
    }

    /**
     * @param Collection<array-key, CfRubricCriterionLevel> $levels
     *
     * @return CFRubricCriteriaLevelDto[]
     */
    private function buildLevels(Collection $levels): array
    {
        return array_map(function ($level) {
            $levelDto = new CFRubricCriteriaLevelDto();
            $levelDto->identifier = Uuid::fromString($level->getIdentifier());
            $levelDto->uri = $level->getUri();
            $levelDto->description = $level->getDescription();
            $levelDto->quality = $level->getQuality();
            $levelDto->score = $level->getScore();
            $levelDto->feedback = $level->getFeedback();
            $levelDto->position = $level->getPosition();
            $levelDto->lastChangeDateTime = $level->getChangedAt();

            return $levelDto;
        }, $levels->toArray());
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
        $this->updateDocument($doc, $packageDto);
        $this->updateItems($doc, $packageDto);
        $this->updateAssociations($doc, $packageDto);
        if ($packageDto->CFDefinitions) {
            $this->handleDefinitions($packageDto->CFDefinitions);
        }
        $this->updateRubrics($packageDto);

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
