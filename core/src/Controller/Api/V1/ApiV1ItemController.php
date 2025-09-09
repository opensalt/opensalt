<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddItemCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\UpdateItemCommand;
use App\DTO\Api\V1\ItemDto;
use App\Entity\Framework\LsDefConcept;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsItemRepository;
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
                required: false,
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

        return new JsonResponse($serialized, Response::HTTP_OK, [
            'Location' => $this->generateUrl('app_api_v1_item_get', ['documentIdentifier' => $doc->getIdentifier(), 'itemIdentifier' => $lsItem->getIdentifier()]),
        ], true);
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
}
