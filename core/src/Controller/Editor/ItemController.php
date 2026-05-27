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
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefItemType;
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

        // Parse request body
        $data = json_decode($request->getContent(), true);

        try {
            if (null !== $data && isset($data['copyFromIdentifier'])) {
                $sourceItem = $this->itemRepository->findOneBy(['identifier' => $data['copyFromIdentifier']]);
                if (null === $sourceItem) {
                    return new JsonResponse(['error' => 'Source item for copy not found.'], Response::HTTP_NOT_FOUND);
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

                $itemType = $data['extensions']['salt:type'] ?? $request->query->get('itemType');

                if (!empty($data)) {
                    $this->applyDataToItem($lsItem, $data, $itemType);
                }
            } else {
                $lsItem = new LsItem();
                $lsItem->setLsDoc($doc);
                $lsItem->setLsDocUri($doc->getUri());

                $itemType = $data['extensions']['salt:type'] ?? $request->query->get('itemType');

                if (null !== $data) {
                    $this->applyDataToItem($lsItem, $data, $itemType);
                }
            }

            $command = new AddItemCommand($lsItem, $doc, $parent);
            $this->sendCommand($command);

            /** @var ?LsAssociation $assoc */
            $assoc = $this->managerRegistry->getRepository(LsAssociation::class)->findOneBy(['originLsItem' => $lsItem, 'type' => LsAssociation::CHILD_OF]);

            return $this->generateItemJsonResponse($lsItem, $assoc);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/item/{identifier}', name: 'editor_item_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(Permission::ITEM_EDIT, 'lsItem')]
    public function updateItem(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsItem $lsItem,
    ): Response {
        // Parse request body
        $data = json_decode($request->getContent(), true);

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

        $itemType = $lsItem->getItemType();
        $itemTypeObj = null;
        if (null !== $itemType) {
            $itemTypeObj = [
                'title' => $itemType->getTitle(),
                'identifier' => $itemType->getIdentifier(),
                'uri' => $itemType->getUri(),
            ];
        }

        $licence = $lsItem->getLicence();
        $licenceObj = null;
        if (null !== $licence) {
            $licenceObj = [
                'identifier' => $licence->getIdentifier(),
                'uri' => $licence->getUri(),
                'title' => $licence->getTitle(),
            ];
        }

        $subjectURIs = [];
        foreach ($lsItem->getSubjects() as $subject) {
            $subjectURIs[] = [
                'identifier' => $subject->getIdentifier(),
                'uri' => $subject->getUri(),
                'title' => $subject->getTitle(),
            ];
        }

        $response = [
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
            'extensions' => $lsItem->getExtra() ?? [],
            'lastChangeDateTime' => $lsItem->getChangedAt()?->format('c'),
            'documentIdentifier' => $doc->getIdentifier(),
            'permissions' => [
                'canEdit' => $this->isGranted(Permission::ITEM_EDIT, $lsItem),
            ],
            'associations' => $this->buildItemAssociationList($lsItem),
        ];

        return new JsonResponse($response);
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

            $originItem = $assoc->getOriginLsItem();
            $originDocItem = $assoc->getOriginLsDoc();
            $destItem = $assoc->getDestinationLsItem();
            $destDoc = $assoc->getDestinationLsDoc();

            $originTitle = null;
            $originIdentifier = $assoc->getOriginNodeIdentifier();
            $originDocumentIdentifier = null;
            $originTargetType = 'item';
            if (null !== $originItem) {
                $hcs = $originItem->getHumanCodingScheme();
                $originTitle = ($hcs ? $hcs.' - ' : '').$originItem->getFullStatement();
                $originDocumentIdentifier = $originItem->getLsDoc()->getIdentifier();
                $originTargetType = 'item';
            } elseif (null !== $originDocItem) {
                $originTitle = $originDocItem->getTitle();
                $originDocumentIdentifier = $originDocItem->getIdentifier();
                $originTargetType = 'document';
            }

            $destTitle = null;
            $destIdentifier = $assoc->getDestinationNodeIdentifier();
            $destDocumentIdentifier = null;
            $destTargetType = 'item';
            if (null !== $destItem) {
                $hcs = $destItem->getHumanCodingScheme();
                $destTitle = ($hcs ? $hcs.' - ' : '').$destItem->getFullStatement();
                $destDocumentIdentifier = $destItem->getLsDoc()->getIdentifier();
                $destTargetType = 'item';
            } elseif (null !== $destDoc) {
                $destTitle = $destDoc->getTitle();
                $destDocumentIdentifier = $destDoc->getIdentifier();
                $destTargetType = 'document';
            }

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
                'originNodeURI' => [
                    'identifier' => $originIdentifier,
                    'title' => $originTitle,
                    'uri' => $assoc->getOriginNodeUri(),
                    'documentIdentifier' => $originDocumentIdentifier,
                ],
                'destinationNodeURI' => [
                    'identifier' => $destIdentifier,
                    'title' => $destTitle,
                    'uri' => $assoc->getDestinationNodeUri(),
                    'documentIdentifier' => $destDocumentIdentifier,
                ],
                'targetType' => $destTargetType,
                'sequenceNumber' => $assoc->getSequenceNumber(),
                'annotation' => $assoc->getNotes(),
                'CFAssociationGroupingURI' => $groupObj,
                'canEdit' => $canEdit,
            ];
        }

        return $associations;
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

        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return new JsonResponse(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        $newParentIdentifier = $data['newParentIdentifier'] ?? null;
        $targetItemIdentifier = $data['targetItemIdentifier'] ?? null;
        $position = $data['position'] ?? 'inside';
        $oldChildOfAssocIdentifier = $data['childOfAssociationIdentifier'] ?? null;

        if (null === $newParentIdentifier) {
            return new JsonResponse(['error' => 'newParentIdentifier is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $em = $this->managerRegistry->getManager();

            if (null !== $oldChildOfAssocIdentifier) {
                $oldAssoc = $this->associationRepository->findOneBy(['identifier' => $oldChildOfAssocIdentifier]);
                if (null !== $oldAssoc) {
                    $this->associationRepository->removeAssociation($oldAssoc);
                    $em->flush();
                }
            } else {
                $deleted = $this->associationRepository->removeAllAssociationsOfType($lsItem, LsAssociation::CHILD_OF);
                $em->flush();
            }

            $newParent = $this->itemRepository->findOneBy(['identifier' => $newParentIdentifier]);
            if (null === $newParent) {
                $newParent = $this->docRepository->findOneBy(['identifier' => $newParentIdentifier]);
            }

            if (null === $newParent) {
                return new JsonResponse(['error' => 'Parent not found.'], Response::HTTP_NOT_FOUND);
            }

            $sequenceNumber = 1;
            if (null !== $targetItemIdentifier && 'inside' !== $position) {
                $sequenceNumber = $this->calculateSequenceNumber(
                    $newParent, $targetItemIdentifier, $position
                );
            } else {
                $sequenceNumber = $this->getNextSequenceNumber($newParent);
            }

            $newAssoc = $lsItem->addParent($newParent, $sequenceNumber);
            $em->persist($newAssoc);
            $em->flush();

            return new JsonResponse([
                'childOfAssociationIdentifier' => $newAssoc->getIdentifier(),
                'sequenceNumber' => $newAssoc->getSequenceNumber(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function calculateSequenceNumber(
        LsItem|LsDoc $parent,
        string $targetItemIdentifier,
        string $position,
    ): int {
        $em = $this->managerRegistry->getManager();
        $parentIdentifier = $parent->getIdentifier();

        $childAssocs = $this->associationRepository->findAllChildAssociationsFor($parentIdentifier);

        $siblings = [];
        foreach ($childAssocs as $assoc) {
            $childItem = $assoc->getOriginLsItem();
            if (null !== $childItem) {
                $siblings[] = [
                    'identifier' => $childItem->getIdentifier(),
                    'seq' => $assoc->getSequenceNumber() ?? 0,
                ];
            }
        }

        usort($siblings, static fn (array $a, array $b) => $a['seq'] <=> $b['seq']);

        $targetIndex = null;
        foreach ($siblings as $i => $s) {
            if ($s['identifier'] === $targetItemIdentifier) {
                $targetIndex = $i;
                break;
            }
        }

        if (null === $targetIndex) {
            $lastSeq = [] !== $siblings ? (int) end($siblings)['seq'] : 0;

            return $lastSeq + 1;
        }

        if ('before' === $position) {
            $targetSeq = (int) $siblings[$targetIndex]['seq'];
            $prevSeq = $targetIndex > 0 ? (int) $siblings[$targetIndex - 1]['seq'] : 0;

            return (int) floor(($prevSeq + $targetSeq) / 2);
        }

        $targetSeq = (int) $siblings[$targetIndex]['seq'];
        $nextIndex = $targetIndex + 1;
        if ($nextIndex < count($siblings)) {
            $nextSeq = (int) $siblings[$nextIndex]['seq'];

            return (int) floor(($targetSeq + $nextSeq) / 2);
        }

        return $targetSeq + 1;
    }

    private function getNextSequenceNumber(LsItem|LsDoc $parent): int
    {
        $parentIdentifier = $parent->getIdentifier();
        $childAssocs = $this->associationRepository->findAllChildAssociationsFor($parentIdentifier);

        $maxSeq = 0;
        foreach ($childAssocs as $assoc) {
            $seq = $assoc->getSequenceNumber();
            if (null !== $seq && $seq > $maxSeq) {
                $maxSeq = $seq;
            }
        }

        return $maxSeq + 1;
    }

    private function applyDataToItem(LsItem $lsItem, array $data, ?string $itemType): void
    {
        if (array_key_exists('licence', $data)) {
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
        if (array_key_exists('subjects', $data)) {
            $subjects = [];
            if (is_array($data['subjects'])) {
                foreach ($data['subjects'] as $subjectValue) {
                    if (empty($subjectValue)) {
                        continue;
                    }
                    $field = is_numeric($subjectValue) ? 'id' : 'identifier';
                    $subject = $this->managerRegistry->getRepository(LsDefSubject::class)
                        ->findOneBy([$field => $subjectValue]);
                    if (null !== $subject) {
                        $subjects[] = $subject;
                    }
                }
            }
            $lsItem->setSubjects($subjects);
        }
        if (array_key_exists('itemType', $data)) {
            $itemTypeValue = $data['itemType'];
            if (empty($itemTypeValue) || '' === $itemTypeValue) {
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

        if (null !== $itemType) {
            $kind = LsItemKind::tryFromName($itemType);
            $dtoClass = $kind->dto();
            if (LsItem::class !== $dtoClass) {
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

                    return;
                }
            }
        }

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
            $educationalAlignment = $data['educationalAlignment'];
            if (is_array($educationalAlignment)) {
                $educationalAlignment = implode(', ', $educationalAlignment);
            }
            $lsItem->setEducationalAlignment($educationalAlignment);
        } elseif (isset($data['educationLevel'])) {
            $educationLevel = $data['educationLevel'];
            if (is_array($educationLevel)) {
                $educationLevel = implode(', ', $educationLevel);
            }
            $lsItem->setEducationalAlignment($educationLevel);
        }
        if (isset($data['additionalFields']) && is_array($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $fieldName => $value) {
                $lsItem->setAdditionalField($fieldName, $value);
            }
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
            'additionalFields' => $item->getAdditionalFields() ?? [],
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
