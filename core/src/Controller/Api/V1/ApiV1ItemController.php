<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddItemCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\UpdateItemCommand;
use App\DTO\Api\V1\ItemDto;
use App\DTO\Api\V1\PatchDto;
use App\DTO\Api\V1\PatchOperation;
use App\Entity\Framework\LsDefConcept;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsItemRepository;
use App\Resolver\JsonPatchRequestPayloadResolver;
use App\Security\Permission;
use App\Util\EducationLevelSet;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
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
    description: 'The item cannot be found',
)]
#[OA\Tag('Item', description: 'Operations on framework items')]
class ApiV1ItemController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly LsItemRepository $itemRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ObjectMapperInterface $objectMapper,
    ) {
    }

    #[Route('/api/v1/packages/{documentIdentifier}/items', name: 'app_api_v1_item_post', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Post(
        operationId: 'api_v1_item_post',
        description: 'Adds a new item to a package',
        summary: 'Add an item',
    )]
    #[OA\RequestBody(content: new Model(type: ItemDto::class, groups: ['create']))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The created item',
        content: new OA\MediaType('application/json', new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    ref: new Model(type: ItemDto::class, groups: ['view']),
                    type: 'object',
                ),
                new OA\Property(
                    property: 'links',
                    type: 'object',
                    nullable: true,
                    additionalProperties: true,
                ),
            ],
            type: 'object',
            additionalProperties: true,
        )),
        links: [new OA\Link(
            link: 'GetItem',
            operationRef: 'api_v1_item_get',
            parameters: [
                'itemIdentifier' => '$response.body#/data/identifier',
                'documentIdentifier' => '$response.body#/data/CFDocumentURI/identifier',
            ],
            description: 'The `identifier` value and `CFDocumentURI/identifier` value can be used as the `itemIdentifier` and `documentIdentifier` parameter in `GET /api/v1/packages/{documentIdentifier}/items/{itemIdentifier}`.',
        )]
    )]
    #[OA\Response(
        response: 202,
        description: 'The URL where the created item will be available',
        headers: [
            new OA\Header(
                header: 'Location',
                description: 'The URL where the created item will be available',
                schema: new OA\Schema(
                    type: 'string',
                    format: 'url',
                ),
            ),
            new OA\Header(
                header: 'Retry-After',
                description: 'How long before trying to fetch the item',
                schema: new OA\Schema(
                    type: 'integer',
                    format: 'seconds',
                    nullable: true,
                ),
            ),
        ],
    )]
    public function postItem(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
        #[MapRequestPayload(validationGroups: ['create'])] ItemDto $item,
    ): Response {
        $lsItem = $doc->createItem($item->identifier->toString());
        $item->uri = $lsItem->getUri();
        $item->lastChangeDateTime ??= new \DateTimeImmutable();

        $lsItem = $this->updateItem($lsItem, $item);

        $parentItem = null;
        if (null !== $item->parentIdentifier) {
            // Get parent item
            $parentItem = $this->itemRepository->findOneBy(['identifier' => $item->parentIdentifier]);
        }

        $assocGroup = null;

        $command = new AddItemCommand($lsItem, $lsItem->getLsDoc(), $parentItem, $assocGroup);
        $this->sendCommand($command);

        $serialized = $this->serializer->serialize(['data' => $lsItem], 'json', []);

        return new JsonResponse($serialized, json: true);
    }

    #[Route('/api/v1/packages/{documentIdentifier}/items/{itemIdentifier}', name: 'app_api_v1_item_get', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'doc')]
    #[OA\Get(
        operationId: 'api_v1_item_get',
        description: 'Fetch a single item',
        summary: 'Get an item',
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The item',
        content: new Model(type: ItemDto::class, groups: ['view']),
    )]
    public function getItem(
        #[MapEntity(mapping: ['documentIdentifier' => 'lsDocIdentifier', 'itemIdentifier' => 'identifier'])] LsItem $item,
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        return new JsonResponse($this->serializer->serialize($item, 'json', []), json: true);
    }

    #[Route('/api/v1/packages/{documentIdentifier}/items/{itemIdentifier}', name: 'app_api_v1_item_put', methods: ['PUT'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Put(
        operationId: 'api_v1_item_put',
        description: 'Update a single item',
        summary: 'Update an item',
    )]
    #[OA\RequestBody(content: new Model(type: ItemDto::class, groups: ['update']))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The updated item',
        content: new Model(type: ItemDto::class, groups: ['view']),
    )]
    public function putItem(
        #[MapEntity(mapping: ['documentIdentifier' => 'lsDocIdentifier', 'itemIdentifier' => 'identifier'])] LsItem $lsItem,
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
        #[MapRequestPayload(validationGroups: ['update'])] ItemDto $item,
    ): Response {
        if (null === $item->identifier) {
            $item->identifier = Uuid::fromString($lsItem->getIdentifier());
        }
        if (null === $item->uri) {
            $item->uri = $lsItem->getUri();
        }
        $item->lastChangeDateTime ??= new \DateTimeImmutable();

        if ($item->identifier->toString() !== $lsItem->getIdentifier()) {
            throw new BadRequestHttpException('The identifier must not be changed.');
        }
        if ($item->uri !== $lsItem->getUri()) {
            throw new BadRequestHttpException('The uri must not be changed.');
        }

        $lsItem = $this->updateItem($lsItem, $item);
        $command = new UpdateItemCommand($lsItem);
        $this->sendCommand($command);

        return new JsonResponse($this->serializer->serialize($lsItem, 'json', []), json: true);
    }

    #[Route('/api/v1/packages/{documentIdentifier}/items/{itemIdentifier}', name: 'app_api_v1_item_patch', methods: ['PATCH'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Patch(
        operationId: 'api_v1_item_patch',
        description: 'Update a single item using a patch',
        summary: 'Update an item',
    )]
    #[OA\RequestBody(content: new OA\MediaType(
        mediaType: 'application/json-patch+json',
        schema: new OA\Schema(
            type: 'array',
            items: new OA\Items(
                required: ['op', 'path'],
                type: 'object',
                oneOf: [
                    new OA\Schema(
                        title: 'Add/Replace',
                        required: ['value'],
                        properties: [
                            new OA\Property(property: 'op', description: 'The operation to perform', type: 'string', enum: ['add', 'replace', 'test']),
                            new OA\Property(property: 'path', description: 'A JSON Pointer path to the target location', type: 'string'),
                            new OA\Property(property: 'value', description: 'The value to add, replace, or test', nullable: true, oneOf: [
                                new OA\Schema(title: 'String', type: 'string'),
                                new OA\Schema(title: 'Number', type: 'number'),
                                new OA\Schema(title: 'Integer', type: 'integer'),
                                new OA\Schema(title: 'Boolean', type: 'boolean'),
                                new OA\Schema(title: 'Array', type: 'array', items: new OA\Items()),
                                new OA\Schema(title: 'Object', type: 'object'),
                            ]),
                        ],
                    ),
                    new OA\Schema(
                        title: 'Remove',
                        properties: [
                            new OA\Property(property: 'op', description: 'The operation to perform', type: 'string', enum: ['remove']),
                            new OA\Property(property: 'path', description: 'A JSON Pointer path to the target location', type: 'string'),
                        ],
                    ),
                    new OA\Schema(
                        title: 'Move/Copy',
                        required: ['from'],
                        properties: [
                            new OA\Property(property: 'op', description: 'The operation to perform', type: 'string', enum: ['move', 'copy']),
                            new OA\Property(property: 'path', description: 'A JSON Pointer path to the target location', type: 'string'),
                            new OA\Property(property: 'from', description: 'The JSON Pointer path to the source location', type: 'string', nullable: true),
                        ],
                    ),
                ],
            ),
        )
    ))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The updated item',
        content: new Model(type: ItemDto::class, groups: ['view']),
    )]
    public function patchItem(
        #[MapEntity(mapping: ['documentIdentifier' => 'lsDocIdentifier', 'itemIdentifier' => 'identifier'])] LsItem $lsItem,
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
        #[MapRequestPayload(resolver: JsonPatchRequestPayloadResolver::class)] ?PatchDto $patch,
    ): Response {
        if (null === $patch || null === $patch->patch) {
            throw new BadRequestHttpException('Request body cannot be empty');
        }

        $this->applyJsonPatch($lsItem, $patch->patch);

        $command = new UpdateItemCommand($lsItem);
        $this->sendCommand($command);

        return new JsonResponse($this->serializer->serialize($lsItem, 'json', []), json: true);
    }

    #[Route('/api/v1/packages/{documentIdentifier}/items/{itemIdentifier}', name: 'app_api_v1_item_delete', methods: ['DELETE'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Delete(
        operationId: 'api_v1_item_delete',
        description: 'Delete a single item',
        summary: 'Delete an item',
    )]
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'The item has been deleted',
    )]
    public function deleteItem(
        #[MapEntity(mapping: ['documentIdentifier' => 'lsDocIdentifier', 'itemIdentifier' => 'identifier'])] LsItem $item,
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        $command = new DeleteItemCommand($item);
        $this->sendCommand($command);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    protected function updateItem(LsItem $lsItem, ItemDto $item): LsItem
    {
        // Use ObjectMapper for direct property mappings
        $this->objectMapper->map($item, $lsItem);

        // Handle properties requiring special logic
        $lsItem->setUri($item->uri ?? $lsItem->getUri());
        $lsItem->setEducationalAlignment(EducationLevelSet::fromStringOrArray($item->educationLevel)->toString());
        $lsItem->setChangedAt($item->lastChangeDateTime ?? new \DateTimeImmutable());

        // Handle related entities with database lookups and error handling
        $lsItem->setLicence(null);
        if (null !== $item->licenseURI) {
            $licence = $this->entityManager->getRepository(LsDefLicence::class)
                ->findOneBy(['identifier' => $item->licenseURI->identifier]);
            if (null === $licence) {
                throw new NotFoundHttpException('The licence URI does not exist.');
            }
            $lsItem->setLicence($licence);
        }

        $lsItem->setConcepts(null);
        if (null !== $item->conceptKeywordsURI) {
            $concepts = [];
            $concept = $this->entityManager->getRepository(LsDefConcept::class)
                ->findOneBy(['identifier' => $item->conceptKeywordsURI->identifier]);
            if (null === $concept) {
                throw new NotFoundHttpException('The conceptKeywords URI does not exist.');
            }
            $concepts[] = $concept;
            $lsItem->setConcepts($concepts);
        }

        $lsItem->setSubjects(null);
        if (null !== $item->subjectURI) {
            $subjects = [];
            foreach ($item->subjectURI as $subjectUri) {
                $subject = $this->entityManager->getRepository(LsDefSubject::class)
                    ->findOneBy(['identifier' => $subjectUri->identifier]);
                if (null === $subject) {
                    throw new NotFoundHttpException('The subject URI does not exist.');
                }
                $subjects[] = $subject;
            }
            $lsItem->setSubjects($subjects);
        }

        $lsItem->setItemType(null);
        if (null !== $item->cfItemTypeURI) {
            $itemType = $this->entityManager->getRepository(LsDefItemType::class)
                ->findOneBy(['identifier' => $item->cfItemTypeURI->identifier]);
            if (null === $itemType) {
                throw new NotFoundHttpException('The cfItemType URI does not exist.');
            }
            $lsItem->setItemType($itemType);
        }

        return $lsItem;
    }

    /**
     * Apply JSON Patch operations to an LsItem.
     */
    private function applyJsonPatch(LsItem $lsItem, array $patchOperations): void
    {
        foreach ($patchOperations as $operation) {
            $this->applyPatchOperation($lsItem, $operation);
        }
    }

    /**
     * Apply a single JSON Patch operation.
     */
    private function applyPatchOperation(LsItem $lsItem, PatchOperation $operation): void
    {
        $path = $operation->path;

        // Convert JSON Pointer to property path
        $propertyPath = $this->jsonPointerToPropertyPath($path);

        switch ($operation->op) {
            case 'add':
                $this->applyAddOperation($lsItem, $propertyPath, $operation->value);
                break;
            case 'replace':
                $this->applyReplaceOperation($lsItem, $propertyPath, $operation->value);
                break;
            case 'remove':
                $this->applyRemoveOperation($lsItem, $propertyPath);
                break;
            case 'move':
                $this->applyMoveOperation($lsItem, $propertyPath, $operation->from);
                break;
            case 'copy':
                $this->applyCopyOperation($lsItem, $propertyPath, $operation->from);
                break;
            case 'test':
                $this->applyTestOperation($lsItem, $propertyPath, $operation->value);
                break;
            default:
                throw new BadRequestHttpException("Unsupported patch operation: {$operation->op}");
        }
    }

    /**
     * Convert JSON Pointer to Symfony PropertyAccess path.
     */
    private function jsonPointerToPropertyPath(string $jsonPointer): string
    {
        // Remove leading slash and handle escaped characters
        $path = ltrim($jsonPointer, '/');

        // Handle JSON Pointer escaping (~0 -> ~, ~1 -> /)
        $path = str_replace(['~0', '~1'], ['~', '/'], $path);

        // Convert to Symfony PropertyAccess format
        // JSON Pointer uses / as separator, PropertyAccess uses .
        return str_replace('/', '.', $path);
    }

    /**
     * Apply add operation.
     */
    private function applyAddOperation(LsItem $lsItem, string $propertyPath, mixed $value): void
    {
        if ($this->propertyAccessor->isReadable($lsItem, $propertyPath)) {
            // Property exists, this is actually a replace operation
            $this->propertyAccessor->setValue($lsItem, $propertyPath, $value);
        } else {
            // Property doesn't exist, we need to handle array/object additions
            $this->addToCollection($lsItem, $propertyPath, $value);
        }
    }

    /**
     * Apply replace operation.
     */
    private function applyReplaceOperation(LsItem $lsItem, string $propertyPath, mixed $value): void
    {
        if (!$this->propertyAccessor->isReadable($lsItem, $propertyPath)) {
            throw new BadRequestHttpException("Cannot replace non-existent property: {$propertyPath}");
        }
        $this->propertyAccessor->setValue($lsItem, $propertyPath, $value);
    }

    /**
     * Apply remove operation.
     */
    private function applyRemoveOperation(LsItem $lsItem, string $propertyPath): void
    {
        if (!$this->propertyAccessor->isReadable($lsItem, $propertyPath)) {
            throw new BadRequestHttpException("Cannot remove non-existent property: {$propertyPath}");
        }
        $this->propertyAccessor->setValue($lsItem, $propertyPath, null);
    }

    /**
     * Apply move operation.
     */
    private function applyMoveOperation(LsItem $lsItem, string $toPath, ?string $fromPath): void
    {
        if (null === $fromPath) {
            throw new BadRequestHttpException("Move operation requires 'from' parameter");
        }

        $fromPropertyPath = $this->jsonPointerToPropertyPath($fromPath);

        if (!$this->propertyAccessor->isReadable($lsItem, $fromPropertyPath)) {
            throw new BadRequestHttpException("Cannot move from non-existent property: {$fromPropertyPath}");
        }

        $value = $this->propertyAccessor->getValue($lsItem, $fromPropertyPath);
        $this->applyAddOperation($lsItem, $toPath, $value);
        $this->applyRemoveOperation($lsItem, $fromPropertyPath);
    }

    /**
     * Apply copy operation.
     */
    private function applyCopyOperation(LsItem $lsItem, string $toPath, ?string $fromPath): void
    {
        if (null === $fromPath) {
            throw new BadRequestHttpException("Copy operation requires 'from' parameter");
        }

        $fromPropertyPath = $this->jsonPointerToPropertyPath($fromPath);

        if (!$this->propertyAccessor->isReadable($lsItem, $fromPropertyPath)) {
            throw new BadRequestHttpException("Cannot copy from non-existent property: {$fromPropertyPath}");
        }

        $value = $this->propertyAccessor->getValue($lsItem, $fromPropertyPath);
        $this->applyAddOperation($lsItem, $toPath, $value);
    }

    /**
     * Apply test operation.
     */
    private function applyTestOperation(LsItem $lsItem, string $propertyPath, mixed $expectedValue): void
    {
        if (!$this->propertyAccessor->isReadable($lsItem, $propertyPath)) {
            throw new BadRequestHttpException("Cannot test non-existent property: {$propertyPath}");
        }

        $actualValue = $this->propertyAccessor->getValue($lsItem, $propertyPath);
        if ($actualValue !== $expectedValue) {
            throw new BadRequestHttpException("Test failed: expected {$expectedValue}, got {$actualValue}");
        }
    }

    /**
     * Add value to a collection (array or object property).
     */
    private function addToCollection(LsItem $lsItem, string $propertyPath, mixed $value): void
    {
        // Split path to get parent and property name
        $lastDot = strrpos($propertyPath, '.');
        if (false === $lastDot) {
            // Root level property
            $this->propertyAccessor->setValue($lsItem, $propertyPath, $value);

            return;
        }

        $parentPath = substr($propertyPath, 0, $lastDot);
        $propertyName = substr($propertyPath, $lastDot + 1);

        if (!$this->propertyAccessor->isReadable($lsItem, $parentPath)) {
            throw new BadRequestHttpException("Cannot add to non-existent parent: {$parentPath}");
        }

        $parent = $this->propertyAccessor->getValue($lsItem, $parentPath);

        if (is_array($parent)) {
            // Handle array operations
            if (is_numeric($propertyName)) {
                $parent[(int) $propertyName] = $value;
            } else {
                $parent[$propertyName] = $value;
            }
            $this->propertyAccessor->setValue($lsItem, $parentPath, $parent);
        } elseif (is_object($parent)) {
            // Handle object property addition
            $parent->{$propertyName} = $value;
            $this->propertyAccessor->setValue($lsItem, $parentPath, $parent);
        } else {
            throw new BadRequestHttpException("Cannot add to non-collection property: {$parentPath}");
        }
    }
}
