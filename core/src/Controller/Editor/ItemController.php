<?php

declare(strict_types=1);

namespace App\Controller\Editor;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddItemCommand;
use App\Command\Framework\CopyItemToDocCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\DeleteItemWithChildrenCommand;
use App\Command\Framework\UpdateItemCommand;
use App\DTO\ItemType\ItemTypeInterface;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Form\DTO\CopyToLsDocDTO;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use App\Security\Permission;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/framework/editor')]
class ItemController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        private readonly LsDocRepository $docRepository,
        private readonly LsItemRepository $itemRepository,
        private readonly LsAssociationRepository $associationRepository,
        private readonly Security $security,
    ) {
    }

    #[Route(path: '/item/new/{parentIdentifier}', name: 'editor_item_new', methods: ['POST'])]
    public function newItem(
        Request $request,
        string $parentIdentifier,
    ): Response {
        $parentItem = $this->itemRepository->findOneBy(['identifier' => $parentIdentifier]);
        $doc = null;
        $parent = null;

        if (null !== $parentItem) {
            $parent = $parentItem;
            $doc = $parent->getLsDoc();
        } else {
            $doc = $this->docRepository->findOneBy(['identifier' => $parentIdentifier]);
        }

        if (null === $doc) {
            return new JsonResponse(['error' => 'Parent framework or item not found.'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(Permission::ITEM_ADD_TO, $doc)) {
            return new JsonResponse(['error' => 'Access Denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->parseJsonBody($request);
        if ($data instanceof Response) {
            return $data;
        }

        try {
            return $this->createAndAddItem($data, $doc, $parent, $request);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function createAndAddItem(array $data, LsDoc $doc, ?LsItem $parent, Request $request): Response
    {
        $lsItem = $this->createItemFromRequest($data, $doc, $request);

        $command = new AddItemCommand($lsItem, $doc, $parent);
        $this->sendCommand($command);

        /** @var ?LsAssociation $assoc */
        $assoc = $this->managerRegistry->getRepository(LsAssociation::class)->findOneBy(['originLsItem' => $lsItem, 'type' => LsAssociation::CHILD_OF]);

        return $this->generateItemJsonResponse($lsItem, $assoc);
    }

    private function createItemFromRequest(?array $data, LsDoc $doc, Request $request): LsItem
    {
        if (null !== $data && isset($data['copyFromIdentifier'])) {
            return $this->createItemFromCopy($data, $doc);
        }

        $lsItem = new LsItem();
        $lsItem->setLsDoc($doc);
        $lsItem->setLsDocUri($doc->getUri());

        $itemType = $data['extensions']['salt:type'] ?? $request->query->get('itemType');

        if (null !== $data) {
            $this->applyDataToItem($lsItem, $data, $itemType);
        }

        return $lsItem;
    }

    private function createItemFromCopy(array &$data, LsDoc $doc): LsItem
    {
        $sourceItem = $this->itemRepository->findOneBy(['identifier' => $data['copyFromIdentifier']]);
        if (null === $sourceItem) {
            throw new \InvalidArgumentException('Source item for copy not found.');
        }

        $dto = new CopyToLsDocDTO();
        $dto->lsItem = $sourceItem;
        $dto->lsDoc = $doc;

        $copyCommand = new CopyItemToDocCommand($dto);
        $this->sendCommand($copyCommand);
        $lsItem = $copyCommand->getNewItem();

        if (!empty($data['addCopyToTitle'])) {
            $lsItem->setFullStatement('Copy of '.$lsItem->getFullStatement());
            $abbreviatedStatement = $lsItem->getAbbreviatedStatement();
            if (null !== $abbreviatedStatement) {
                $lsItem->setAbbreviatedStatement('Copy of '.$abbreviatedStatement);
            }
        }

        unset($data['copyFromIdentifier'], $data['addCopyToTitle'], $data['title'], $data['fullStatement']);

        if (!empty($data)) {
            $itemType = $data['extensions']['salt:type'] ?? null;
            $this->applyDataToItem($lsItem, $data, $itemType);
        }

        return $lsItem;
    }

    #[Route(path: '/item/{identifier}', name: 'editor_item_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(Permission::ITEM_EDIT, 'lsItem')]
    public function updateItem(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsItem $lsItem,
    ): Response {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        // Extract itemType from extensions.salt:type, fall back to query parameter
        $itemType = $data['extensions']['salt:type'] ?? $request->query->get('itemType');

        if (null !== $data) {
            $this->applyDataToItem($lsItem, $data, $itemType);
        }

        try {
            $command = new UpdateItemCommand($lsItem);
            $this->sendCommand($command);

            return $this->generateItemJsonResponse($lsItem);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/item/{identifier}', name: 'editor_item_delete', methods: ['DELETE'])]
    #[IsGranted(Permission::ITEM_EDIT, 'lsItem')]
    public function deleteItem(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsItem $lsItem,
        #[MapQueryParameter] int $includingChildren = 0,
    ): Response {
        try {
            if (0 === $includingChildren) {
                $command = new DeleteItemCommand($lsItem);
            } else {
                $command = new DeleteItemWithChildrenCommand($lsItem);
            }
            $this->sendCommand($command);

            return new JsonResponse(['status' => 'OK'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/item/{identifier}/details', name: 'editor_item_details', methods: ['GET'])]
    public function getItemDetails(
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsItem $lsItem,
    ): Response {
        $doc = $lsItem->getLsDoc();
        if (!$this->isGranted(Permission::FRAMEWORK_VIEW, $doc)) {
            return new JsonResponse(['error' => 'Access Denied.'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->buildItemDetailsResponse($lsItem));
    }

    private function buildItemDetailsResponse(LsItem $lsItem): array
    {
        $itemType = $lsItem->getItemType();
        $itemTypeObj = null !== $itemType ? [
            'title' => $itemType->getTitle(),
            'identifier' => $itemType->getIdentifier(),
            'uri' => $itemType->getUri(),
        ] : null;

        $licence = $lsItem->getLicence();
        $licenceObj = null !== $licence ? [
            'identifier' => $licence->getIdentifier(),
            'uri' => $licence->getUri(),
            'title' => $licence->getTitle(),
        ] : null;

        $subjectURIs = array_map(static fn ($subject) => [
            'identifier' => $subject->getIdentifier(),
            'uri' => $subject->getUri(),
            'title' => $subject->getTitle(),
        ], $lsItem->getSubjects()->toArray());

        return [
            'identifier' => $lsItem->getIdentifier(),
            'uri' => $lsItem->getUri(),
            'fullStatement' => $lsItem->getFullStatement(),
            'abbreviatedStatement' => $lsItem->getAbbreviatedStatement(),
            'humanCodingScheme' => $lsItem->getHumanCodingScheme(),
            'listEnumeration' => $lsItem->getListEnumInSource(),
            'notes' => $lsItem->getNotes(),
            'language' => $lsItem->getLanguage(),
            'educationLevel' => $lsItem->getEducationalAlignment(),
            'conceptKeywords' => $lsItem->getConceptKeywordsString(),
            'itemType' => $itemType?->getTitle(),
            'CFItemTypeURI' => $itemTypeObj,
            'statusStartDate' => $lsItem->getStatusStart()?->format('Y-m-d'),
            'statusEndDate' => $lsItem->getStatusEnd()?->format('Y-m-d'),
            'subject' => $lsItem->getSubject(),
            'subjectURI' => $subjectURIs,
            'licenseURI' => $licenceObj,
            'licence' => $licence?->getIdentifier(),
            'extensions' => $lsItem->getExtra(),
            'lastChangeDateTime' => $lsItem->getChangedAt()->format('c'),
            'documentIdentifier' => $lsItem->getLsDoc()->getIdentifier(),
            'permissions' => [
                'canEdit' => $this->isGranted(Permission::ITEM_EDIT, $lsItem),
            ],
            'associations' => $this->buildItemAssociationList($lsItem),
        ];
    }

    private function buildItemAssociationList(LsItem $item): array
    {
        $associations = [];

        $qb = $this->associationRepository->createQueryBuilder('a')
            ->leftJoin('a.originLsItem', 'oi')
            ->leftJoin('a.originLsDoc', 'od')
            ->leftJoin('a.destinationLsItem', 'di')
            ->leftJoin('a.destinationLsDoc', 'dd')
            ->leftJoin('a.group', 'g')
            ->where('a.originLsItem = :item OR a.destinationLsItem = :item')
            ->setParameter('item', $item->getId())
            ->orderBy('a.sequenceNumber', 'ASC');

        $results = $qb->getQuery()->getResult();

        foreach ($results as $assoc) {
            $originDoc = $assoc->getOriginLsItem()?->getLsDoc() ?? $assoc->getOriginLsDoc();
            $destDoc = $assoc->getDestinationLsItem()?->getLsDoc() ?? $assoc->getDestinationLsDoc();

            if (null !== $originDoc && !$this->isGranted(Permission::FRAMEWORK_VIEW, $originDoc)) {
                continue;
            }
            if (null !== $destDoc && !$this->isGranted(Permission::FRAMEWORK_VIEW, $destDoc)) {
                continue;
            }

            $originInfo = $this->buildNodeInfo($assoc->getOriginLsItem(), $assoc->getOriginLsDoc(), $assoc->getOriginNodeIdentifier(), $assoc->getOriginNodeUri());
            $destInfo = $this->buildNodeInfo($assoc->getDestinationLsItem(), $assoc->getDestinationLsDoc(), $assoc->getDestinationNodeIdentifier(), $assoc->getDestinationNodeUri());

            $group = $assoc->getGroup();
            $groupObj = null;
            if (null !== $group) {
                $groupObj = [
                    'identifier' => $group->getIdentifier(),
                    'title' => $group->getTitle(),
                ];
            }

            $assocDoc = $assoc->getLsDoc();
            $canEdit = null !== $assocDoc && $this->isGranted(Permission::ASSOCIATION_EDIT, $assoc);

            $associations[] = [
                'identifier' => $assoc->getIdentifier(),
                'associationType' => $assoc->getType(),
                'associationDocumentIdentifier' => $assocDoc?->getIdentifier(),
                'originNodeURI' => $originInfo,
                'destinationNodeURI' => $destInfo,
                'targetType' => $destInfo['targetType'],
                'sequenceNumber' => $assoc->getSequenceNumber(),
                'annotation' => $assoc->getNotes(),
                'CFAssociationGroupingURI' => $groupObj,
                'canEdit' => $canEdit,
            ];
        }

        return $associations;
    }

    private function buildNodeInfo(?LsItem $lsItem, ?LsDoc $lsDoc, ?string $identifier, ?string $uri): array
    {
        $title = null;
        $documentIdentifier = null;
        $targetType = 'item';

        if (null !== $lsItem) {
            $hcs = $lsItem->getHumanCodingScheme();
            $title = ($hcs ? $hcs.' - ' : '').$lsItem->getFullStatement();
            $documentIdentifier = $lsItem->getLsDoc()->getIdentifier();
        } elseif (null !== $lsDoc) {
            $title = $lsDoc->getTitle();
            $documentIdentifier = $lsDoc->getIdentifier();
            $targetType = 'document';
        }

        return [
            'identifier' => $identifier,
            'title' => $title,
            'uri' => $uri,
            'documentIdentifier' => $documentIdentifier,
            'targetType' => $targetType,
        ];
    }

    #[Route(path: '/item/{identifier}/move', name: 'editor_item_move', methods: ['POST'])]
    public function moveItem(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsItem $lsItem,
    ): Response {
        $doc = $lsItem->getLsDoc();
        if (!$this->isGranted(Permission::FRAMEWORK_EDIT, $doc)) {
            return new JsonResponse(['error' => 'Access Denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->parseJsonBody($request);
        if ($data instanceof Response) {
            return $data;
        }

        $newParentIdentifier = $data['newParentIdentifier'] ?? null;
        $targetItemIdentifier = $data['targetItemIdentifier'] ?? null;
        $position = $data['position'] ?? 'inside';
        $existingAssocIdentifier = $data['childOfAssociationIdentifier'] ?? null;

        if (null === $newParentIdentifier) {
            return new JsonResponse(['error' => 'newParentIdentifier is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->executeMove($lsItem, $newParentIdentifier, $targetItemIdentifier, $position, $existingAssocIdentifier);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function executeMove(
        LsItem $lsItem,
        string $newParentIdentifier,
        ?string $targetItemIdentifier,
        string $position,
        ?string $existingAssocIdentifier,
    ): JsonResponse {
        $em = $this->managerRegistry->getManager();

        $existingAssoc = $this->findExistingChildOfAssociation($lsItem, $existingAssocIdentifier);
        if (null === $existingAssoc) {
            return new JsonResponse(['error' => 'No existing isChildOf association found for this item.'], Response::HTTP_NOT_FOUND);
        }

        $newParent = $this->resolveParent($newParentIdentifier);
        if (null === $newParent) {
            return new JsonResponse(['error' => 'Parent not found.'], Response::HTTP_NOT_FOUND);
        }

        $oldParent = $existingAssoc->getDestination();
        $parentChanged = is_object($oldParent) && !$this->isSameParent($oldParent, $newParent);

        if ($parentChanged) {
            $this->reparentAssociation($existingAssoc, $oldParent, $newParent);
        }

        $em->flush();

        $siblingSequenceNumbers = $this->renumberSiblings($newParent, $lsItem->getIdentifier(), $targetItemIdentifier, $position);

        if ($parentChanged) {
            $this->renumberSiblings($oldParent);
        }

        $em->flush();

        return new JsonResponse([
            'childOfAssociationIdentifier' => $existingAssoc->getIdentifier(),
            'sequenceNumber' => $existingAssoc->getSequenceNumber(),
            'siblingSequenceNumbers' => $siblingSequenceNumbers,
        ], Response::HTTP_OK);
    }

    /**
     * Renumber all children of a parent with sequential integers starting from 1.
     *
     * Sorts by current sequence number, physically reorders the moved item
     * to the desired position, then assigns clean sequential values.
     *
     * @return array<string, int> Map of item identifier => assigned sequence number
     */
    private function renumberSiblings(
        LsItem|LsDoc $parent,
        ?string $movedItemIdentifier = null,
        ?string $targetItemIdentifier = null,
        ?string $position = null,
    ): array {
        $parentIdentifier = $parent->getIdentifier();
        $childAssocs = $this->associationRepository->findAllChildAssociationsFor($parentIdentifier);

        usort($childAssocs, static fn (LsAssociation $a, LsAssociation $b): int => ($a->getSequenceNumber() ?? 0) <=> ($b->getSequenceNumber() ?? 0));

        if (null !== $movedItemIdentifier && null !== $targetItemIdentifier && null !== $position && 'inside' !== $position) {
            $this->reorderSiblings($childAssocs, $movedItemIdentifier, $targetItemIdentifier, $position);
        }

        $result = [];
        $seq = 1;
        foreach ($childAssocs as $assoc) {
            $assoc->setSequenceNumber($seq);
            $childItem = $assoc->getOriginLsItem();
            if (null !== $childItem) {
                $result[$childItem->getIdentifier()] = $seq;
            }
            ++$seq;
        }

        return $result;
    }

    private function findExistingChildOfAssociation(LsItem $lsItem, ?string $assocIdentifier): ?LsAssociation
    {
        if (null !== $assocIdentifier) {
            $assoc = $this->associationRepository->findOneBy(['identifier' => $assocIdentifier]);
            if (null !== $assoc && $assoc->getOriginLsItem()?->getIdentifier() === $lsItem->getIdentifier()) {
                return $assoc;
            }
        }

        foreach ($lsItem->getAssociations() as $assoc) {
            if (LsAssociation::CHILD_OF === $assoc->getType()) {
                return $assoc;
            }
        }

        return null;
    }

    /**
     * @param array<LsAssociation> $childAssocs Passed by reference; reordered in place
     */
    private function reorderSiblings(array &$childAssocs, string $movedItemIdentifier, string $targetItemIdentifier, string $position): void
    {
        $movedIndex = null;
        $targetIndex = null;
        foreach ($childAssocs as $i => $assoc) {
            $id = $assoc->getOriginLsItem()?->getIdentifier();
            if ($id === $movedItemIdentifier) {
                $movedIndex = $i;
            }
            if ($id === $targetItemIdentifier) {
                $targetIndex = $i;
            }
        }

        if (null === $movedIndex || null === $targetIndex) {
            return;
        }

        $movedAssoc = array_splice($childAssocs, $movedIndex, 1)[0];
        if ($targetIndex > $movedIndex) {
            --$targetIndex;
        }
        if ('before' === $position) {
            array_splice($childAssocs, $targetIndex, 0, [$movedAssoc]);
        } else {
            array_splice($childAssocs, (int) ($targetIndex + 1), 0, [$movedAssoc]);
        }
    }

    private function isSameParent(object $a, object $b): bool
    {
        if ($a === $b) {
            return true;
        }

        return $a->getIdentifier() === $b->getIdentifier();
    }

    private function reparentAssociation(LsAssociation $assoc, LsItem|LsDoc $oldParent, LsItem|LsDoc $newParent): void
    {
        $oldParent->removeInverseAssociation($assoc);
        $assoc->setDestinationLsItem(null);
        $assoc->setDestinationLsDoc(null);
        $assoc->setDestination($newParent);
        $newParent->addInverseAssociation($assoc);
    }

    private function resolveParent(string $identifier): LsItem|LsDoc|null
    {
        return $this->itemRepository->findOneBy(['identifier' => $identifier])
            ?? $this->docRepository->findOneBy(['identifier' => $identifier]);
    }

    private function parseJsonBody(Request $request): array|Response
    {
        try {
            return json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function applyDataToItem(LsItem $lsItem, array $data, ?string $itemType): void
    {
        $this->applyItemLicence($lsItem, $data);
        $this->applyItemSubjects($lsItem, $data);
        $this->applyItemItemType($lsItem, $data);

        if ($this->applyItemDto($lsItem, $data, $itemType)) {
            return;
        }

        $this->applyItemScalarFields($lsItem, $data);
        $this->applyItemAdditionalFields($lsItem, $data);
    }

    private function applyItemLicence(LsItem $lsItem, array $data): void
    {
        if (!array_key_exists('licence', $data)) {
            return;
        }

        if (null !== $data['licence'] && '' !== $data['licence']) {
            $field = is_numeric($data['licence']) ? 'id' : 'identifier';
            $licence = $this->managerRegistry->getRepository(LsDefLicence::class)
                ->findOneBy([$field => $data['licence']]);
            if (null !== $licence) {
                $lsItem->setLicence($licence);
            }
        } else {
            $lsItem->setLicence(null);
        }
    }

    private function applyItemSubjects(LsItem $lsItem, array $data): void
    {
        if (!array_key_exists('subjects', $data)) {
            return;
        }

        $subjects = [];
        if (is_array($data['subjects'])) {
            foreach ($data['subjects'] as $subjectValue) {
                $subject = $this->resolveSubject($subjectValue);
                if (null !== $subject) {
                    $subjects[] = $subject;
                }
            }
        }
        $lsItem->setSubjects($subjects);
    }

    private function resolveSubject(mixed $subjectValue): ?LsDefSubject
    {
        if (empty($subjectValue)) {
            return null;
        }

        if (str_starts_with((string) $subjectValue, '__')) {
            $cleanValue = substr((string) $subjectValue, 2);
            $newSubject = new LsDefSubject();
            $newSubject->setTitle($cleanValue);
            $newSubject->setHierarchyCode($cleanValue);
            $this->managerRegistry->getManager()->persist($newSubject);

            return $newSubject;
        }

        $field = is_numeric($subjectValue) ? 'id' : 'identifier';

        return $this->managerRegistry->getRepository(LsDefSubject::class)
            ->findOneBy([$field => $subjectValue]);
    }

    private function applyItemItemType(LsItem $lsItem, array $data): void
    {
        if (!array_key_exists('itemType', $data)) {
            return;
        }

        $itemTypeValue = $data['itemType'];
        if (empty($itemTypeValue)) {
            $lsItem->setItemType(null);
        } elseif (str_starts_with((string) $itemTypeValue, '__')) {
            $cleanValue = substr((string) $itemTypeValue, 2);
            $newType = new LsDefItemType();
            $newType->setCode($cleanValue);
            $newType->setTitle($cleanValue);
            $newType->setHierarchyCode($cleanValue);
            $this->managerRegistry->getManager()->persist($newType);
            $lsItem->setItemType($newType);
        } else {
            $field = is_numeric($itemTypeValue) ? 'id' : 'identifier';
            $existingType = $this->managerRegistry->getRepository(LsDefItemType::class)
                ->findOneBy([$field => $itemTypeValue]);
            if (null !== $existingType) {
                $lsItem->setItemType($existingType);
            }
        }
    }

    private function applyItemDto(LsItem $lsItem, array $data, ?string $itemType): bool
    {
        if (null === $itemType) {
            return false;
        }

        $kind = LsItemKind::tryFromName($itemType);
        $dtoClass = $kind->dto();
        if (LsItem::class === $dtoClass) {
            return false;
        }

        $dto = $dtoClass::fromItem($lsItem);

        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }

        if ($dto instanceof ItemTypeInterface) {
            $lsItem->setDiscriminator($dto::ITEM_TYPE_IDENTIFIER);
            $dto->applyToItem($lsItem, $this->htmlSanitizer);

            if (isset($data['notes'])) {
                $lsItem->setNotes($data['notes']);
            }

            return true;
        }

        return false;
    }

    private function applyItemScalarFields(LsItem $lsItem, array $data): void
    {
        if (isset($data['fullStatement'])) {
            $lsItem->setFullStatement($data['fullStatement']);
        }
        if (isset($data['abbreviatedStatement'])) {
            $lsItem->setAbbreviatedStatement($data['abbreviatedStatement']);
        }
        if (isset($data['humanCodingScheme'])) {
            $lsItem->setHumanCodingScheme($data['humanCodingScheme']);
        }
        if (isset($data['listEnumeration'])) {
            $lsItem->setListEnumInSource($data['listEnumeration']);
        } elseif (isset($data['listEnumInSource'])) {
            $lsItem->setListEnumInSource($data['listEnumInSource']);
        }
        if (isset($data['conceptKeywords'])) {
            $conceptKeywords = $data['conceptKeywords'];
            if (is_array($conceptKeywords)) {
                $conceptKeywords = implode(', ', $conceptKeywords);
            }
            $lsItem->setConceptKeywords($conceptKeywords);
        }
        if (isset($data['notes'])) {
            $lsItem->setNotes($data['notes']);
        }
        if (isset($data['language'])) {
            $lsItem->setLanguage($data['language']);
        }
        if (isset($data['educationalAlignment'])) {
            $lsItem->setEducationalAlignment($this->flattenArrayValue($data['educationalAlignment']));
        } elseif (isset($data['educationLevel'])) {
            $lsItem->setEducationalAlignment($this->flattenArrayValue($data['educationLevel']));
        }
    }

    private function flattenArrayValue(string|array $value): string
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }

        return $value;
    }

    private function applyItemAdditionalFields(LsItem $lsItem, array $data): void
    {
        if (!isset($data['additionalFields']) || !is_array($data['additionalFields'])) {
            return;
        }

        foreach ($data['additionalFields'] as $fieldName => $value) {
            $lsItem->setAdditionalField($fieldName, $value);
        }
    }

    private function generateItemJsonResponse(LsItem $item, ?LsAssociation $assoc = null): Response
    {
        // Mimic LsItemController::generateItemJsonResponse
        $ret = [
            'id' => $item->getId(),
            'identifier' => $item->getIdentifier(),
            'uri' => $item->getUri(),
            'objectType' => $item->getObjectType(),
            'fullStatement' => $item->getFullStatement(),
            'humanCodingScheme' => $item->getHumanCodingScheme(),
            'listEnumInSource' => $item->getListEnumInSource(),
            'abbreviatedStatement' => $item->getAbbreviatedStatement(),
            'conceptKeywords' => $item->getConceptKeywordsString(),
            'conceptKeywordsUri' => $item->getConceptKeywordsUri(),
            'notes' => $item->getNotes(),
            'language' => $item->getLanguage(),
            'educationalAlignment' => $item->getEducationalAlignment(),
            'itemType' => $item->getItemType()?->getTitle(),
            'changedAt' => $item->getChangedAt(),
            'extra' => $item->getExtra(),
            'additionalFields' => $item->getAdditionalFields(),
            'assocData' => [],
        ];

        if (null !== $assoc) {
            $destItem = $assoc->getDestinationNodeIdentifier();

            if (null !== $destItem) {
                $ret['assocData'] = [
                    'assocDoc' => $assoc->getLsDocIdentifier(),
                    'assocId' => $assoc->getId(),
                    'identifier' => $assoc->getIdentifier(),
                    'dest' => ['doc' => $assoc->getLsDocIdentifier(), 'item' => $destItem, 'uri' => $destItem],
                ];
                if (null !== $assoc->getGroup()) {
                    $ret['assocData']['groupId'] = $assoc->getGroup()->getId();
                    $ret['assocData']['groupIdentifier'] = $assoc->getGroup()->getIdentifier();
                }
                if (!in_array($assoc->getSequenceNumber(), [null, 0], true)) {
                    $ret['assocData']['seq'] = $assoc->getSequenceNumber();
                }
            }
        }

        return new JsonResponse($ret);
    }
}
