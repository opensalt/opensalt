<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddDocumentCommand;
use App\Command\Framework\UpdateDocumentCommand;
use App\Controller\Api\UriController;
use App\DTO\Api\V1\DocumentDto;
use App\DTO\Api\V1\DocumentFilterDto;
use App\DTO\Api\V1\DocumentListResponseDto;
use App\DTO\Api\V1\DocumentPaginationDto;
use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use App\Security\Permission;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    description: 'The document cannot be found',
)]
#[OA\Tag('Document')]
class ApiV1DocumentController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly LsDocRepository $lsDocRepository,
        private readonly ObjectMapperInterface $objectMapper,
    ) {
    }

    #[Route('/api/v1/documents', methods: ['GET'])]
    #[OA\Get(
        operationId: 'api_v1_document_index',
        description: 'Get a list of documents with pagination and filtering',
        summary: 'List documents',
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Maximum number of documents to return (1-100)',
        in: 'query',
        schema: new OA\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1)
    )]
    #[OA\Parameter(
        name: 'cursor',
        description: 'Cursor for pagination (base64 encoded document ID)',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'direction',
        description: 'Pagination direction',
        in: 'query',
        schema: new OA\Schema(type: 'string', default: 'next', enum: ['next', 'prev'])
    )]
    #[OA\Parameter(
        name: 'creator',
        description: 'Filter by creator name (partial match)',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'title',
        description: 'Filter by title (partial match)',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'adoptionStatus',
        description: 'Filter by adoption status',
        in: 'query',
        schema: new OA\Schema(type: 'string', enum: ['Private Draft', 'Draft', 'Adopted', 'Deprecated'])
    )]
    #[OA\Parameter(
        name: 'subject',
        description: 'Filter by subject (exact match)',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'language',
        description: 'Filter by language code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'caseVersion',
        description: 'Filter by CASE version',
        in: 'query',
        schema: new OA\Schema(type: 'string', enum: ['1.1'])
    )]
    #[OA\Parameter(
        name: 'publisher',
        description: 'Filter by publisher (partial match)',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'sort',
        description: 'Sort field',
        in: 'query',
        schema: new OA\Schema(type: 'string', default: 'updatedAt', enum: ['updatedAt', 'title', 'identifier', 'lastChangeDateTime'])
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort direction',
        in: 'query',
        schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'List of documents',
        content: new Model(type: DocumentListResponseDto::class),
    )]
    public function index(
        #[MapQueryString] DocumentPaginationDto $pagination,
        #[MapQueryString] DocumentFilterDto $filter,
    ): Response {
        // Get documents with pagination and filtering
        // TODO: Filter to only the frameworks the user can see
        $result = $this->lsDocRepository->findDocumentsWithPagination($pagination, $filter);

        return new JsonResponse($this->serializer->serialize($result, 'json', []), json: true);
    }

    #[Route('/api/v1/packages/-/document', methods: ['POST'])]
    #[Route('/api/v1/documents', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    #[OA\Post(
        // operationId: 'api_v1_document_create',
        description: 'Create a new document',
        summary: 'Create document',
    )]
    #[OA\RequestBody(content: new Model(type: DocumentDto::class, groups: ['create']))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Document created successfully',
        content: new Model(type: DocumentDto::class, groups: ['view'])
    )]
    public function postDocument(
        #[MapRequestPayload(validationGroups: ['create'])] DocumentDto $documentDto,
    ): Response {
        $lsDoc = new LsDoc($documentDto->identifier);
        $documentDto->identifier = Uuid::fromString($lsDoc->getIdentifier());
        $documentDto->uri ??= $lsDoc->getUri();
        $this->objectMapper->map($documentDto, $lsDoc);

        $command = new AddDocumentCommand($lsDoc);
        $this->sendCommand($command);

        return new JsonResponse(
            $this->serializer->serialize($lsDoc, 'json', ['groups' => ['view']]),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/api/v1/packages/{documentIdentifier}/document', methods: ['GET'])]
    #[Route('/api/v1/documents/{documentIdentifier}', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'doc')]
    #[OA\Get(
        // operationId: 'api_v1_document_show',
        description: 'Get a single document',
        summary: 'Get document',
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The document',
        content: new Model(type: DocumentDto::class, groups: ['view']),
    )]
    public function getDocument(
        Request $request,
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        return new JsonResponse($this->serializer->serialize($doc, 'json', [
            'json_encode_options' => \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION,
        ]), Response::HTTP_OK, json: true);
    }

    #[Route('/api/v1/packages/{documentIdentifier}/document', methods: ['PUT'])]
    #[Route('/api/v1/documents/{documentIdentifier}', methods: ['PUT'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Put(
        // operationId: 'api_v1_document_update',
        description: 'Update an existing document',
        summary: 'Update document',
    )]
    #[OA\RequestBody(content: new Model(type: DocumentDto::class, groups: ['update']))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Document updated successfully',
        content: new Model(type: DocumentDto::class, groups: ['view'])
    )]
    public function putDocument(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
        #[MapRequestPayload(validationGroups: ['update'])] DocumentDto $documentDto,
    ): Response {
        $documentDto->identifier = Uuid::fromString($doc->getIdentifier());
        $documentDto->uri = $doc->getUri();

        // Update document properties using ObjectMapper
        $this->objectMapper->map($documentDto, $doc);

        $command = new UpdateDocumentCommand($doc);
        $this->sendCommand($command);

        return new JsonResponse(
            $this->serializer->serialize($doc, 'json', ['groups' => ['view']]),
            Response::HTTP_OK,
            [],
            true
        );
    }
}
