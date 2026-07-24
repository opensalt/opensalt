<?php

declare(strict_types=1);

namespace App\Articulations\Service;

final class RequirementTreeBuilder
{
    private const int MAX_DEPTH = 10;

    private const ITEM_TYPE_REQUIREMENT_SET = 'Requirement Set';
    private const ITEM_TYPE_REQUIREMENT_GROUP = 'Requirement Group';
    private const ITEM_TYPE_COURSE = 'Course';
    private const ITEM_TYPE_SERIES = 'Series';

    /**
     * @param array<string, array{identifier: string, uri: string, courseCode: ?string, itemType: ?string, extensions: array}> $itemsById
     * @param array<string, list<string>> $partOfChildrenByParentId
     *
     * @return array<string, mixed>
     */
    public function buildRequirement(
        string $originItemId,
        array $itemsById,
        array $partOfChildrenByParentId,
        int $depth = 0,
        array $visited = [],
    ): array {
        if ($depth > self::MAX_DEPTH || isset($visited[$originItemId])) {
            throw new \InvalidArgumentException(sprintf('Cycle or depth limit at "%s".', $originItemId));
        }

        $visited[$originItemId] = true;

        $item = $this->requireItem($originItemId, $itemsById);
        $itemType = $item['itemType'] ?? null;

        if (self::ITEM_TYPE_REQUIREMENT_SET === $itemType) {
            return [
                'op' => 'anyOf',
                'members' => $this->buildMemberRequirements($originItemId, $itemsById, $partOfChildrenByParentId, $depth + 1, $visited),
            ];
        }

        if (self::ITEM_TYPE_REQUIREMENT_GROUP === $itemType) {
            return [
                'op' => 'allOf',
                'members' => $this->buildMemberRequirements($originItemId, $itemsById, $partOfChildrenByParentId, $depth + 1, $visited),
            ];
        }

        return $this->courseRef($item);
    }

    /**
     * @param array<string, array{identifier: string, uri: string, courseCode: ?string, itemType: ?string, extensions: array}> $itemsById
     * @param array<string, list<string>> $partOfChildrenByParentId
     *
     * @return array<string, mixed>
     */
    public function buildReceiving(string $destinationItemId, array $itemsById, array $partOfChildrenByParentId): array
    {
        $item = $this->requireItem($destinationItemId, $itemsById);
        $itemType = $item['itemType'] ?? null;

        if (self::ITEM_TYPE_SERIES === $itemType) {
            $childIds = $partOfChildrenByParentId[$destinationItemId] ?? [];
            $courses = [];
            foreach ($childIds as $childId) {
                $child = $this->requireItem($childId, $itemsById);
                $courses[] = $this->courseRef($child);
            }

            return [
                'series' => [
                    'conjunction' => 'and',
                    'courseCode' => $item['courseCode'] ?? '',
                    'identifier' => $item['identifier'],
                    'uri' => $item['uri'],
                    'courses' => $courses,
                ],
            ];
        }

        return [
            'course' => $this->courseRef($item),
        ];
    }

    /**
     * @param array<string, array{identifier: string, uri: string, courseCode: ?string, itemType: ?string, extensions: array}> $itemsById
     * @param array<string, list<string>> $partOfChildrenByParentId
     * @param array<string, true> $visited
     *
     * @return list<array<string, mixed>>
     */
    private function buildMemberRequirements(
        string $parentId,
        array $itemsById,
        array $partOfChildrenByParentId,
        int $depth,
        array $visited,
    ): array {
        $members = [];
        foreach ($partOfChildrenByParentId[$parentId] ?? [] as $childId) {
            $members[] = $this->buildRequirement($childId, $itemsById, $partOfChildrenByParentId, $depth, $visited);
        }

        return $members;
    }

    /**
     * @param array{identifier: string, uri: string, courseCode: ?string, itemType: ?string, extensions: array} $item
     *
     * @return array{courseCode: string, identifier: string, uri: string}
     */
    private function courseRef(array $item): array
    {
        return [
            'courseCode' => $item['courseCode'] ?? '',
            'identifier' => $item['identifier'],
            'uri' => $item['uri'],
        ];
    }

    /**
     * @param array<string, array{identifier: string, uri: string, courseCode: ?string, itemType: ?string, extensions: array}> $itemsById
     *
     * @return array{identifier: string, uri: string, courseCode: ?string, itemType: ?string, extensions: array}
     */
    private function requireItem(string $itemId, array $itemsById): array
    {
        if (!isset($itemsById[$itemId])) {
            throw new \InvalidArgumentException(sprintf('Unknown item identifier "%s".', $itemId));
        }

        return $itemsById[$itemId];
    }
}
