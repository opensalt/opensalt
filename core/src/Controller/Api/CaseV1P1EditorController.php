<?php

namespace App\Controller\Api;

use App\DTO\Api\V1\DocumentDto;
use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use App\Repository\Framework\LsDocRepository;
use App\Security\Permission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class CaseV1P1EditorController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly LsDocRepository $lsDocRepository,
        private readonly Security $security,
    ) {
    }

    #[Route('/ims/case/v1p1/CFDocuments/{documentIdentifier}/related', methods: ['GET'], name: 'api_v1p1_document_related')]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'doc')]
    #[OA\Get(
        operationId: 'api_v1_document_related',
        description: 'Get related documents for a document',
        summary: 'Get related documents',
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'List of related documents',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: DocumentDto::class, groups: ['view']))
        )
    )]
    public function getRelatedDocuments(
        Request $request,
        #[MapEntity(mapping: ['documentIdentifier' => 'identifier'])] LsDoc $doc,
    ): Response {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            $user = null;
        }

        $relatedDocs = $this->lsDocRepository->findRelatedDocuments($doc, $user);

        return new JsonResponse(
            $this->serializer->serialize($relatedDocs, 'json', ['groups' => ['view']]),
            Response::HTTP_OK,
            [],
            true
        );
    }
}
