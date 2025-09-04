<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddAssociationCommand;
use App\Command\Framework\DeleteAssociationCommand;
use App\Command\Framework\UpdateAssociationCommand;
use App\DTO\Api\V1\AssociationDto;
use App\Entity\Framework\LsAssociation;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
    description: 'The token does not have access to the association',
)]
#[OA\Response(
    response: 404,
    description: 'The association cannot be found',
)]
#[OA\Tag('Association', description: 'Operations on framework associations')]
class ApiV1AssociationController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ObjectMapperInterface $objectMapper,
    ) {
    }

    #[Route('/api/v1/packages/{documentIdentifier}/associations', name: 'app_api_v1_association_post', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Post(
        operationId: 'api_v1_association_post',
        description: 'Adds a new association to a package',
        summary: 'Add an association',
    )]
    #[OA\RequestBody(content: new Model(type: AssociationDto::class, groups: ['create']))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The created association',
        content: new OA\MediaType('application/json', new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    ref: new Model(type: AssociationDto::class, groups: ['view']),
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
            link: 'GetAssociation',
            operationRef: 'api_v1_association_get',
            parameters: [
                'associationIdentifier' => '$response.body#/data/identifier',
                'documentIdentifier' => '$response.body#/data/CFDocumentURI/identifier',
            ],
            description: 'The `identifier` value and `CFDocumentURI/identifier` value can be used as the `associationIdentifier` and `documentIdentifier` parameter in `GET /api/v1/packages/{documentIdentifier}/associations/{associationIdentifier}`.',
        )]
    )]
    #[OA\Response(
        response: 202,
        description: 'The URL where the created association will be available',
        headers: [
            new OA\Header(
                header: 'Location',
                description: 'The URL where the created association will be available',
                schema: new OA\Schema(
                    type: 'string',
                    format: 'url',
                ),
            ),
            new OA\Header(
                header: 'Retry-After',
                description: 'How long before trying to fetch the association',
                schema: new OA\Schema(
                    type: 'integer',
                    format: 'seconds',
                    nullable: true,
                ),
            ),
        ],
    )]
    public function postAssociation(
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
        #[MapRequestPayload(validationGroups: ['create'])] AssociationDto $association,
    ): Response {
        $lsAssociation = $doc->createAssociation($association->identifier->toString());
        $association->uri = $lsAssociation->getUri();
        $association->lastChangeDateTime ??= new \DateTimeImmutable();

        $lsAssociation = $this->updateAssociation($lsAssociation, $association);

        $command = new AddAssociationCommand($lsAssociation);
        $this->sendCommand($command);

        $serialized = $this->serializer->serialize(['data' => $lsAssociation], 'json', []);
        dump($serialized);

        return new JsonResponse($serialized, json: true);
    }

    #[Route('/api/v1/packages/{documentIdentifier}/associations/{associationIdentifier}', name: 'app_api_v1_association_get', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'doc')]
    #[OA\Get(
        operationId: 'api_v1_association_get',
        description: 'Fetch a single association',
        summary: 'Get an association',
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The association',
        content: new Model(type: AssociationDto::class, groups: ['view']),
    )]
    public function getAssociation(
        #[MapEntity(mapping: ['documentIdentifier' => 'lsDocIdentifier', 'associationIdentifier' => 'identifier'])] LsAssociation $association,
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        return new JsonResponse($this->serializer->serialize($association, 'json', []), json: true);
    }

    #[Route('/api/v1/packages/{documentIdentifier}/associations/{associationIdentifier}', name: 'app_api_v1_association_put', methods: ['PUT'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Put(
        operationId: 'api_v1_association_put',
        description: 'Update a single association',
        summary: 'Update an association',
    )]
    #[OA\RequestBody(content: new Model(type: AssociationDto::class, groups: ['update']))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The updated association',
        content: new Model(type: AssociationDto::class, groups: ['view']),
    )]
    public function putAssociation(
        #[MapEntity(mapping: ['documentIdentifier' => 'lsDocIdentifier', 'associationIdentifier' => 'identifier'])] LsAssociation $lsAssociation,
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
        #[MapRequestPayload(validationGroups: ['update'])] AssociationDto $association,
    ): Response {
        if (null === $association->identifier) {
            $association->identifier = Uuid::fromString($lsAssociation->getIdentifier());
        }
        if (null === $association->uri) {
            $association->uri = $lsAssociation->getUri();
        }
        $association->lastChangeDateTime ??= new \DateTimeImmutable();

        if ($association->identifier->toString() !== $lsAssociation->getIdentifier()) {
            throw new BadRequestHttpException('The identifier must not be changed.');
        }
        if ($association->uri !== $lsAssociation->getUri()) {
            throw new BadRequestHttpException('The uri must not be changed.');
        }

        $lsAssociation = $this->updateAssociation($lsAssociation, $association);
        $command = new UpdateAssociationCommand($lsAssociation);
        $this->sendCommand($command);

        return new JsonResponse($this->serializer->serialize($lsAssociation, 'json', []), json: true);
    }

    #[Route('/api/v1/packages/{documentIdentifier}/associations/{associationIdentifier}', name: 'app_api_v1_association_delete', methods: ['DELETE'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    #[OA\Delete(
        operationId: 'api_v1_association_delete',
        description: 'Delete a single association',
        summary: 'Delete an association',
    )]
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'The association has been deleted',
    )]
    public function deleteAssociation(
        #[MapEntity(mapping: ['documentIdentifier' => 'lsDocIdentifier', 'associationIdentifier' => 'identifier'])] LsAssociation $association,
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        $command = new DeleteAssociationCommand($association);
        $this->sendCommand($command);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    protected function updateAssociation(LsAssociation $lsAssociation, AssociationDto $association): LsAssociation
    {
        // Use ObjectMapper for direct property mappings
        try {
            $this->objectMapper->map($association, $lsAssociation);
        } catch (\Throwable $exception) {
            throw new BadRequestHttpException('The passed association data was not valid.', $exception);
        }

        // Handle properties requiring special logic
        $lsAssociation->setUri($association->uri ?? $lsAssociation->getUri());
        $lsAssociation->setChangedAt($association->lastChangeDateTime ?? new \DateTimeImmutable());

        // Handle related entities with database lookups and error handling
        // Adapt for association-specific fields, e.g., origin, destination, type

        return $lsAssociation;
    }
}
