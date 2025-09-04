<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddDocumentCommand;
use App\Command\Framework\DeleteDocumentCommand;
use App\Command\Framework\UpdateDocumentCommand;
use App\Controller\Api\UriController;
use App\DTO\Api\V1\DocumentDto;
use App\Entity\Framework\LsDoc;
use App\Security\Permission;
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
#[OA\Tag('Package', description: 'Operations on framework packages')]
class ApiV1PackageController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ObjectMapperInterface $objectMapper,
    ) {
    }

    #[Route('/api/v1/packages/{documentIdentifier}', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'doc')]
    #[OA\Get(
        operationId: 'api_v1_package_get',
        description: 'Get a single package',
        summary: 'Get framework package',
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The package',
        content: new Model(type: DocumentDto::class, groups: ['view']),
    )]
    public function getPackage(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        return $this->forward(UriController::class.'::findUri', [
            'uri' => 'p'.$doc->getIdentifier(),
            '_format' => 'json',
        ]);
    }

    #[Route('/api/v1/packages', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    #[OA\Post(
        operationId: 'api_v1_package_post',
        description: 'Create a new package',
        summary: 'Create package',
    )]
    #[OA\RequestBody(content: new Model(type: DocumentDto::class, groups: ['create']))]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Package created successfully',
        content: new Model(type: DocumentDto::class, groups: ['view'])
    )]
    public function postPackage(
        #[MapRequestPayload(validationGroups: ['create'])] DocumentDto $documentDto,
    ): Response {
        $lsDoc = new LsDoc();
        $lsDoc->setIdentifier($documentDto->identifier);

        if (null === $documentDto->identifier) {
            $documentDto->identifier = Uuid::fromString($lsDoc->getIdentifier());
        }
        if (null === $documentDto->uri) {
            $documentDto->uri = $lsDoc->getUri();
        }

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

    #[Route('/api/v1/packages/{documentIdentifier}', methods: ['PUT'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Put(
        operationId: 'api_v1_package_put',
        description: 'Replace an existing package',
        summary: 'Replace package',
    )]
    #[OA\RequestBody(content: new Model(type: DocumentDto::class, groups: ['update']))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Package updated successfully',
        content: new Model(type: DocumentDto::class, groups: ['view'])
    )]
    public function putPackage(
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

    #[Route('/api/v1/packages/{documentIdentifier}', methods: ['DELETE'])]
    #[IsGranted(Permission::FRAMEWORK_DELETE, 'doc')]
    #[OA\Delete(
        operationId: 'api_v1_package_delete',
        description: 'Delete a framework package',
        summary: 'Delete package',
    )]
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'The package has been deleted',
    )]
    public function deletePackage(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        $command = new DeleteDocumentCommand($doc);
        $this->sendCommand($command);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
