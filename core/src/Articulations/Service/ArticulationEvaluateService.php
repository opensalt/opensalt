<?php

declare(strict_types=1);

namespace App\Articulations\Service;

use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Model\InstitutionMatch;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsItemRepository;
use App\Service\Api1Uris;

final class ArticulationEvaluateService
{
    private const string ASSOC_TYPE_ARTICULATION = 'ext:articulation';
    private const string EXT_ARTICULATION_KEY = 'ais:articulationKey';

    public function __construct(
        private readonly RequirementTreeBuilder $treeBuilder,
        private readonly RequirementEvaluator $evaluator,
        private readonly LsItemRepository $itemRepository,
        private readonly LsAssociationRepository $associationRepository,
        private readonly SendingCourseMatcher $sendingCourseMatcher,
        private readonly Api1Uris $api1Uris,
    ) {
    }

    /**
     * @param list<EntityIdentifiers> $sendingCourses
     *
     * @return array<string, mixed>
     */
    public function evaluateDocument(
        LsDoc $doc,
        InstitutionMatch $sending,
        InstitutionMatch $receiving,
        array $sendingCourses,
    ): array {
        $associations = $this->associationRepository->findByLsDocAndTypes(
            $doc,
            [self::ASSOC_TYPE_ARTICULATION, LsAssociation::PART_OF],
        );

        $endpointIds = $this->collectAssociationNodeIdentifiers($associations);
        $graphItems = $this->itemRepository->findByIdentifiersAnyDoc($endpointIds);
        // Keep any package-local items that might not appear as endpoints (defensive merge)
        $packageItems = $this->itemRepository->findBy(['lsDoc' => $doc]);
        $itemsById = $this->indexItems([...$packageItems, ...$graphItems]);

        $partOfChildrenByParentId = $this->indexPartOfChildren($associations);
        $candidateCourses = $this->articulationLinkedCourses($associations, $graphItems);
        $matchedCourseIds = $this->resolveMatchedCourseIds($sendingCourses, $candidateCourses);
        $isCourseMatched = static fn (array $leaf): bool => isset($matchedCourseIds[$leaf['identifier']]);

        $results = [];
        foreach ($this->articulationAssociations($associations) as $association) {
            $originId = $association->getOriginNodeIdentifier();
            $destinationId = $association->getDestinationNodeIdentifier();
            if (null === $originId || null === $destinationId) {
                continue;
            }

            try {
                $requirement = $this->treeBuilder->buildRequirement(
                    $originId,
                    $itemsById,
                    $partOfChildrenByParentId,
                );
            } catch (\InvalidArgumentException) {
                continue;
            }

            $evaluated = $this->evaluator->evaluate($requirement, $isCourseMatched);
            $status = $evaluated['status'] ?? null;
            if (!in_array($status, ['satisfied', 'partial'], true)) {
                continue;
            }

            $results[] = [
                'status' => $status,
                'articulationKey' => $this->articulationKey($association),
                'receiving' => $this->buildReceivingPayload($destinationId, $itemsById, $partOfChildrenByParentId),
                'requirement' => $evaluated,
                'paths' => $this->evaluator->paths($evaluated),
            ];
        }

        return [
            'sendingInstitution' => $this->institutionToArray($sending),
            'receivingInstitution' => $this->institutionToArray($receiving),
            'articulationDocument' => [
                'identifier' => $doc->getIdentifier(),
                'uri' => $this->api1Uris->getUri($doc) ?? '',
            ],
            'academicYear' => $this->academicYear($doc),
            'results' => $results,
        ];
    }

    /**
     * @param array<string, array{identifier: string, uri: string, courseCode: ?string, itemType: ?string, extensions: array}> $itemsById
     * @param array<string, list<string>> $partOfChildrenByParentId
     *
     * @return array<string, mixed>
     */
    private function buildReceivingPayload(
        string $destinationId,
        array $itemsById,
        array $partOfChildrenByParentId,
    ): array {
        try {
            return $this->treeBuilder->buildReceiving($destinationId, $itemsById, $partOfChildrenByParentId);
        } catch (\InvalidArgumentException) {
            if (!isset($itemsById[$destinationId])) {
                return ['course' => null];
            }

            $item = $itemsById[$destinationId];

            return [
                'course' => [
                    'courseCode' => $item['courseCode'] ?? '',
                    'identifier' => $item['identifier'],
                    'uri' => $item['uri'],
                ],
            ];
        }
    }

    /**
     * @param list<LsItem> $items
     *
     * @return array<string, array{identifier: string, uri: string, courseCode: ?string, itemType: ?string, extensions: array}>
     */
    private function indexItems(array $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $identifier = $item->getIdentifier();
            $indexed[$identifier] = [
                'identifier' => $identifier,
                'uri' => $this->api1Uris->getUri($item) ?? '',
                'courseCode' => $item->getHumanCodingScheme(),
                'itemType' => $item->getItemTypeTitle(),
                'extensions' => $item->getExtensions(),
            ];
        }

        return $indexed;
    }

    /**
     * @param list<LsAssociation> $associations
     *
     * @return array<string, list<string>>
     */
    private function indexPartOfChildren(array $associations): array
    {
        $childrenByParent = [];
        foreach ($associations as $association) {
            if (LsAssociation::PART_OF !== $association->getType()) {
                continue;
            }

            $childId = $association->getOriginNodeIdentifier();
            $parentId = $association->getDestinationNodeIdentifier();
            if (null === $childId || null === $parentId) {
                continue;
            }

            $childrenByParent[$parentId][] = $childId;
        }

        return $childrenByParent;
    }

    /**
     * @param list<LsAssociation> $associations
     *
     * @return list<string>
     */
    private function collectAssociationNodeIdentifiers(array $associations): array
    {
        $ids = [];
        foreach ($associations as $association) {
            $origin = $association->getOriginNodeIdentifier();
            $destination = $association->getDestinationNodeIdentifier();
            if (null !== $origin && '' !== $origin) {
                $ids[$origin] = $origin;
            }
            if (null !== $destination && '' !== $destination) {
                $ids[$destination] = $destination;
            }
        }

        return array_values($ids);
    }

    /**
     * @param list<LsAssociation> $associations
     * @param list<LsItem> $graphItems
     *
     * @return list<LsItem>
     */
    private function articulationLinkedCourses(array $associations, array $graphItems): array
    {
        $originIds = [];
        foreach ($associations as $association) {
            $type = $association->getType();
            if (self::ASSOC_TYPE_ARTICULATION !== $type && LsAssociation::PART_OF !== $type) {
                continue;
            }
            $origin = $association->getOriginNodeIdentifier();
            if (null !== $origin && '' !== $origin) {
                $originIds[$origin] = true;
            }
        }

        $courses = [];
        foreach ($graphItems as $item) {
            if (!isset($originIds[$item->getIdentifier()])) {
                continue;
            }
            if (LsItemKind::Course->value !== $item->getDiscriminator()) {
                continue;
            }
            $courses[] = $item;
        }

        return $courses;
    }

    /**
     * @param list<EntityIdentifiers> $sendingCourses
     * @param list<LsItem> $candidateCourses
     *
     * @return array<string, true>
     */
    private function resolveMatchedCourseIds(array $sendingCourses, array $candidateCourses): array
    {
        $matched = [];
        foreach ($sendingCourses as $request) {
            foreach ($this->sendingCourseMatcher->matchIdentifiers($request, $candidateCourses) as $identifier) {
                $matched[$identifier] = true;
            }
        }

        return $matched;
    }

    /**
     * @param list<LsAssociation> $associations
     *
     * @return list<LsAssociation>
     */
    private function articulationAssociations(array $associations): array
    {
        $articulations = [];
        foreach ($associations as $association) {
            if (self::ASSOC_TYPE_ARTICULATION === $association->getType()) {
                $articulations[] = $association;
            }
        }

        return $articulations;
    }

    private function articulationKey(LsAssociation $association): ?string
    {
        $key = $association->getExtensions()[self::EXT_ARTICULATION_KEY] ?? null;
        if (null === $key || '' === (string) $key) {
            return null;
        }

        return (string) $key;
    }

    private function academicYear(LsDoc $doc): ?string
    {
        $year = $doc->getExtensions()['ais:academicYear'] ?? null;
        if (is_array($year) && isset($year['code'])) {
            return (string) $year['code'];
        }
        if (is_string($year) && '' !== $year) {
            return $year;
        }

        return null;
    }

    /**
     * @return array{name?: string, identifiers: list<array{type: string, value: string}>}
     */
    private function institutionToArray(InstitutionMatch $match): array
    {
        $identifiers = [
            [
                'type' => $match->matchedIdentifier->type,
                'value' => $match->matchedIdentifier->value,
            ],
        ];

        if ('identifier' !== $match->matchedIdentifier->type) {
            $identifiers[] = [
                'type' => 'identifier',
                'value' => $match->item->getIdentifier(),
            ];
        }

        $payload = ['identifiers' => $identifiers];

        $name = $this->institutionName($match->item);
        if (null !== $name && '' !== $name) {
            $payload['name'] = $name;
        }

        return $payload;
    }

    private function institutionName(LsItem $item): ?string
    {
        $legalName = $item->getExtensions()['sdo:legalName'] ?? null;
        if (is_string($legalName) && '' !== $legalName) {
            return $legalName;
        }

        $abbreviated = $item->getAbbreviatedStatement();
        if (null !== $abbreviated && '' !== $abbreviated) {
            return $abbreviated;
        }

        $full = $item->getFullStatement();
        if (null !== $full && '' !== $full) {
            return $full;
        }

        return null;
    }
}
