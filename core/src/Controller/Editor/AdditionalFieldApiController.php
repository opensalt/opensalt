<?php

declare(strict_types=1);

namespace App\Controller\Editor;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\AdditionalFieldRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/framework/editor/additional-fields')]
#[IsGranted('ROLE_USER')]
class AdditionalFieldApiController extends AbstractController
{
    public function __construct(
        private readonly AdditionalFieldRepository $additionalFieldRepository,
    ) {
    }

    #[Route(path: '/{appliesTo}', name: 'editor_additional_fields_list', methods: ['GET'])]
    public function listAdditionalFields(string $appliesTo): JsonResponse
    {
        $resolvedClass = match ($appliesTo) {
            'item' => LsItem::class,
            'doc' => LsDoc::class,
            'association' => LsAssociation::class,
            default => null,
        };

        if (null === $resolvedClass) {
            return new JsonResponse([]);
        }

        $fields = $this->additionalFieldRepository->findBy(['appliesTo' => $resolvedClass]);

        $result = [];
        foreach ($fields as $field) {
            $result[] = [
                'id' => $field->getId(),
                'name' => $field->getName(),
                'displayName' => $field->getDisplayName(),
                'type' => $field->getType(),
                'typeInfo' => $field->getTypeInfo(),
                'appliesTo' => $field->getAppliesTo(),
            ];
        }

        return new JsonResponse($result);
    }
}
