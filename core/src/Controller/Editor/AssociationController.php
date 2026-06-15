<?php

declare(strict_types=1);

namespace App\Controller\Editor;

use App\Attribute\ReadOnlySession;
use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddTreeAssociationCommand;
use App\Command\Framework\DeleteAssociationCommand;
use App\Command\Framework\UpdateAssociationCommand;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsAssociationRepository;
use App\Security\Permission;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/framework/editor')]
class AssociationController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly LsAssociationRepository $associationRepository,
    ) {
    }

    #[Route(path: '/associations/item/{identifier}', name: 'editor_api_item_associations', methods: ['GET'])]
    #[ReadOnlySession]
    public function getItemAssociations(
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsItem $item,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(Permission::FRAMEWORK_VIEW, $item->getLsDoc());
        $frameworkId = $request->query->get('frameworkId');
        $limit = (int) $request->query->get('limit', 1000);
        $offset = (int) $request->query->get('offset', 0);

        $associations = $this->associationRepository->findForItem(
            $item->getIdentifier(),
            $frameworkId,
            $limit,
            $offset
        );

        $response = [
            'data' => [],
            'total' => $associations['total'],
            'itemIdentifier' => $item->getIdentifier(),
        ];

        foreach ($associations['items'] as $assoc) {
            $assocEntity = $assoc[0];
            $assocData = $this->buildAssociationResponse($assocEntity, $assoc);
            if (null !== $assocData) {
                $response['data'][] = $assocData;
            }
        }

        return new JsonResponse($response);
    }

    #[Route(path: '/associations/document/{identifier}', name: 'editor_api_document_associations', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'doc')]
    #[ReadOnlySession]
    public function getDocumentAssociations(
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsDoc $doc,
        Request $request,
    ): Response {
        $limit = (int) $request->query->get('limit', 1000);
        $offset = (int) $request->query->get('offset', 0);

        $associations = $this->associationRepository->findByDocument(
            $doc,
            $limit,
            $offset
        );

        $response = [
            'data' => [],
            'total' => $associations['total'],
            'documentIdentifier' => $doc->getIdentifier(),
        ];

        foreach ($associations['items'] as $assoc) {
            $assocEntity = $assoc[0];
            $assocData = $this->buildAssociationResponse($assocEntity, $assoc);
            if (null !== $assocData) {
                $response['data'][] = $assocData;
            }
        }

        return new JsonResponse($response);
    }

    #[Route(path: '/associations/framework/{identifier}', name: 'editor_api_framework_associations', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'doc')]
    #[ReadOnlySession]
    public function getFrameworkAssociations(
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsDoc $doc,
        Request $request,
    ): Response {
        $limit = (int) $request->query->get('limit', 1000);
        $offset = (int) $request->query->get('offset', 0);

        $associations = $this->associationRepository->findAllForFramework(
            $doc->getIdentifier(),
            $limit,
            $offset
        );

        $response = [
            'data' => [],
            'total' => $associations['total'],
            'frameworkIdentifier' => $doc->getIdentifier(),
        ];

        foreach ($associations['items'] as $assoc) {
            $assocEntity = $assoc[0];
            $assocData = $this->buildAssociationResponse($assocEntity, $assoc);
            if (null !== $assocData) {
                $response['data'][] = $assocData;
            }
        }

        return new JsonResponse($response);
    }

    private function buildAssociationResponse(LsAssociation $assocEntity, array $assoc): ?array
    {
        $originItem = $assocEntity->getOriginLsItem();
        $originDoc = $assocEntity->getOriginLsDoc();
        $destItem = $assocEntity->getDestinationLsItem();
        $destDoc = $assocEntity->getDestinationLsDoc();

        $originFrameworkDoc = $originItem?->getLsDoc() ?? $originDoc;
        $destFrameworkDoc = $destItem?->getLsDoc() ?? $destDoc;

        if (null !== $originFrameworkDoc && !$this->isGranted(Permission::FRAMEWORK_VIEW, $originFrameworkDoc)) {
            return null;
        }
        if (null !== $destFrameworkDoc && !$this->isGranted(Permission::FRAMEWORK_VIEW, $destFrameworkDoc)) {
            return null;
        }

        $originDocumentIdentifier = $originItem?->getLsDoc()?->getIdentifier()
            ?? $originDoc?->getIdentifier();
        $destDocumentIdentifier = $destItem?->getLsDoc()?->getIdentifier()
            ?? $destDoc?->getIdentifier();

        $originTargetType = 'item';
        if (null !== $originDoc) {
            $originTargetType = 'document';
        } elseif (null === $originItem) {
            $originTargetType = 'uri';
        }

        $destTargetType = 'item';
        if (null !== $destDoc) {
            $destTargetType = 'document';
        } elseif (null === $destItem) {
            $destTargetType = 'uri';
        }

        $originHcs = $assoc['origin_human_coding_scheme'] ?? $originItem?->getHumanCodingScheme();
        $originFs = $assoc['origin_full_statement'] ?? $originItem?->getFullStatement();
        $originAbs = $assoc['origin_abbreviated_statement'] ?? $originItem?->getAbbreviatedStatement();
        if (null === $originFs && null !== $originDoc) {
            $originFs = $originDoc->getTitle();
        }

        $destHcs = $assoc['destination_human_coding_scheme'] ?? $destItem?->getHumanCodingScheme();
        $destFs = $assoc['destination_full_statement'] ?? $destItem?->getFullStatement();
        $destAbs = $assoc['destination_abbreviated_statement'] ?? $destItem?->getAbbreviatedStatement();
        if (null === $destFs && null !== $destDoc) {
            $destFs = $destDoc->getTitle();
        }

        $group = $assocEntity->getGroup();
        $groupObj = null;
        if (null !== $group) {
            $groupObj = [
                'identifier' => $group->getIdentifier(),
                'title' => $group->getTitle(),
            ];
        }

        return [
            'identifier' => $assocEntity->getIdentifier(),
            'associationType' => $assocEntity->getType(),
            'associationDocumentIdentifier' => $assocEntity->getLsDoc()?->getIdentifier(),
            'originNodeURI' => [
                'identifier' => $assocEntity->getOriginNodeIdentifier(),
                'title' => $this->buildNodeTitle(
                    $originHcs,
                    $originFs,
                    $originAbs,
                    $assocEntity->getOriginNodeIdentifier(),
                ),
                'uri' => $assocEntity->getOriginNodeUri(),
                'documentIdentifier' => $originDocumentIdentifier,
                'humanCodingScheme' => $originHcs,
                'fullStatement' => $originFs,
                'abbreviatedStatement' => $originAbs,
                'targetType' => $originTargetType,
            ],
            'destinationNodeURI' => [
                'identifier' => $assocEntity->getDestinationNodeIdentifier(),
                'title' => $this->buildNodeTitle(
                    $destHcs,
                    $destFs,
                    $destAbs,
                    $assocEntity->getDestinationNodeIdentifier(),
                ),
                'uri' => $assocEntity->getDestinationNodeUri(),
                'documentIdentifier' => $destDocumentIdentifier,
                'humanCodingScheme' => $destHcs,
                'fullStatement' => $destFs,
                'abbreviatedStatement' => $destAbs,
                'targetType' => $destTargetType,
            ],
            'targetType' => $destTargetType,
            'sequenceNumber' => $assocEntity->getSequenceNumber(),
            'annotation' => $assocEntity->getNotes(),
            'CFAssociationGroupingURI' => $groupObj,
            'additionalFields' => $assocEntity->getAdditionalFields(),
            'extensions' => $assocEntity->getExtensions(),
            'canEdit' => $this->isGranted(Permission::ASSOCIATION_EDIT, $assocEntity),
        ];
    }

    private function buildNodeTitle(?string $hcs, ?string $fullStatement, ?string $abbreviatedStatement, ?string $fallback = null): ?string
    {
        $displayStatement = $abbreviatedStatement ?? $fullStatement;

        if (('' !== ($hcs ?? '')) && null !== $displayStatement) {
            return $hcs.': '.$displayStatement;
        }

        return $displayStatement ?? $fallback;
    }

    #[Route(path: '/association/new/{identifier}', name: 'editor_association_new', methods: ['POST'])]
    #[IsGranted(Permission::ASSOCIATION_ADD_TO, 'lsDoc')]
    public function newAssociation(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsDoc $lsDoc,
    ): Response {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new AddTreeAssociationCommand(
                $lsDoc,
                $data['origin'] ?? null,
                $data['type'] ?? null,
                $data['dest'] ?? null,
                $data['assocGroup'] ?? null,
                $data['annotation'] ?? null,
                $data['extensions'] ?? null
            );
            $this->sendCommand($command);
            $lsAssociation = $command->getAssociation();

            if (null !== $lsAssociation && isset($data['additionalFields']) && is_array($data['additionalFields'])) {
                foreach ($data['additionalFields'] as $fieldName => $value) {
                    $lsAssociation->setAdditionalField($fieldName, $value);
                }
            }

            return new JsonResponse([
                'id' => $lsAssociation?->getId(),
                'identifier' => $lsAssociation?->getIdentifier(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/association/{identifier}', name: 'editor_association_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(Permission::ASSOCIATION_EDIT, 'lsAssociation')]
    public function updateAssociation(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsAssociation $lsAssociation,
    ): Response {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if (null !== $data) {
            if (isset($data['type'])) {
                $lsAssociation->setType($data['type']);
            }
            if (isset($data['annotation'])) {
                $lsAssociation->setNotes($data['annotation']);
            }
            if (isset($data['sequenceNumber'])) {
                $lsAssociation->setSequenceNumber((int) $data['sequenceNumber']);
            }
            if (isset($data['additionalFields']) && is_array($data['additionalFields'])) {
                foreach ($data['additionalFields'] as $fieldName => $value) {
                    $lsAssociation->setAdditionalField($fieldName, $value);
                }
            }
            if (isset($data['extensions']) && is_array($data['extensions'])) {
                foreach ($data['extensions'] as $key => $value) {
                    $lsAssociation->setExtensionProperty((string) $key, $value);
                }
            }
            // assocGroup might be complex if it's an entity,
            // but for now let's assume we handle it via the group's identifier if needed.
        }

        try {
            $command = new UpdateAssociationCommand($lsAssociation);
            $this->sendCommand($command);

            return new JsonResponse([
                'id' => $lsAssociation->getId(),
                'identifier' => $lsAssociation->getIdentifier(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/association/{identifier}', name: 'editor_association_delete', methods: ['DELETE'])]
    #[IsGranted(Permission::ASSOCIATION_EDIT, 'lsAssociation')]
    public function deleteAssociation(
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsAssociation $lsAssociation,
    ): Response {
        try {
            $command = new DeleteAssociationCommand($lsAssociation);
            $this->sendCommand($command);

            return new JsonResponse(['status' => 'OK'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
