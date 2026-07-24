<?php

declare(strict_types=1);

namespace App\Articulations\Service;

final class RequirementEvaluator
{
    private const int MAX_DEPTH = 10;

    /**
     * @param array<string, mixed> $requirement
     * @param callable(array<string, mixed>): bool $isCourseMatched
     *
     * @return array<string, mixed>
     */
    public function evaluate(array $requirement, callable $isCourseMatched, int $depth = 0): array
    {
        if (isset($requirement['op'])) {
            return $this->evaluateGroup($requirement, $isCourseMatched, $depth);
        }

        $match = $isCourseMatched($requirement);

        return array_merge($requirement, [
            'match' => $match,
            'status' => $match ? 'satisfied' : 'unsatisfied',
        ]);
    }

    /**
     * @param array<string, mixed> $evaluatedRequirement
     *
     * @return array{truncated: bool, options: list<array{status: string, require: list<array{courseCode: string, identifier: string, uri: string}>}>}
     */
    public function paths(array $evaluatedRequirement, int $cap = 32): array
    {
        $options = $this->collectPathOptions($evaluatedRequirement, $cap);
        $truncated = count($options) >= $cap && $this->wouldExceedCap($evaluatedRequirement, $cap);

        return [
            'truncated' => $truncated,
            'options' => array_slice($options, 0, $cap),
        ];
    }

    /**
     * @param array<string, mixed> $requirement
     * @param callable(array<string, mixed>): bool $isCourseMatched
     *
     * @return array<string, mixed>
     */
    private function evaluateGroup(array $requirement, callable $isCourseMatched, int $depth): array
    {
        if ($depth > self::MAX_DEPTH) {
            throw new \InvalidArgumentException('Cycle or depth limit in requirement evaluation.');
        }

        $members = [];
        foreach ($requirement['members'] as $member) {
            $members[] = $this->evaluate($member, $isCourseMatched, $depth + 1);
        }

        $status = $this->groupStatus($requirement['op'], $members);

        return [
            'op' => $requirement['op'],
            'status' => $status,
            'members' => $members,
        ];
    }

    /**
     * @param list<array<string, mixed>> $members
     */
    private function groupStatus(string $op, array $members): string
    {
        $anySatisfied = false;
        $allSatisfied = true;
        $anyLeafMatched = false;

        foreach ($members as $member) {
            if ('satisfied' === $member['status']) {
                $anySatisfied = true;
            } else {
                $allSatisfied = false;
            }

            if ($this->hasDescendantLeafMatched($member)) {
                $anyLeafMatched = true;
            }
        }

        if ('allOf' === $op) {
            if ($allSatisfied) {
                return 'satisfied';
            }

            return $anyLeafMatched ? 'partial' : 'unsatisfied';
        }

        if ($anySatisfied) {
            return 'satisfied';
        }

        return $anyLeafMatched ? 'partial' : 'unsatisfied';
    }

    /**
     * @param array<string, mixed> $node
     */
    private function hasDescendantLeafMatched(array $node, int $depth = 0): bool
    {
        if ($depth > self::MAX_DEPTH) {
            return false;
        }

        if (!isset($node['op'])) {
            return !empty($node['match']);
        }

        foreach ($node['members'] as $member) {
            if ($this->hasDescendantLeafMatched($member, $depth + 1)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return list<array{status: string, require: list<array{courseCode: string, identifier: string, uri: string}>}>
     */
    private function collectPathOptions(array $node, int $cap, int $depth = 0): array
    {
        if ($depth > self::MAX_DEPTH) {
            return [];
        }

        if (!isset($node['op'])) {
            return [[
                'status' => !empty($node['match']) ? 'satisfied' : 'unsatisfied',
                'require' => [$this->courseRef($node)],
            ]];
        }

        $childOptions = [];
        foreach ($node['members'] as $member) {
            $childOptions[] = $this->collectPathOptions($member, $cap, $depth + 1);
        }

        if ('anyOf' === $node['op']) {
            return $this->concatenateOptions($childOptions, $cap);
        }

        return $this->cartesianProductOptions($childOptions, $cap);
    }

    /**
     * @param list<list<array{status: string, require: list<array{courseCode: string, identifier: string, uri: string}>}>> $lists
     *
     * @return list<array{status: string, require: list<array{courseCode: string, identifier: string, uri: string}>}>
     */
    private function concatenateOptions(array $lists, int $cap): array
    {
        $result = [];
        foreach ($lists as $list) {
            foreach ($list as $option) {
                if (count($result) >= $cap) {
                    return $result;
                }
                $result[] = $option;
            }
        }

        return $result;
    }

    /**
     * @param list<list<array{status: string, require: list<array{courseCode: string, identifier: string, uri: string}>}>> $lists
     *
     * @return list<array{status: string, require: list<array{courseCode: string, identifier: string, uri: string}>}>
     */
    private function cartesianProductOptions(array $lists, int $cap): array
    {
        if ([] === $lists) {
            return [];
        }

        $result = [[]];
        foreach ($lists as $list) {
            $next = [];
            foreach ($result as $partial) {
                foreach ($list as $option) {
                    if (count($next) >= $cap) {
                        return $next;
                    }
                    $next[] = [] === $partial
                        ? $option
                        : $this->mergePathOptions($partial, $option);
                }
            }
            $result = $next;
        }

        return $result;
    }

    /**
     * @param array{status: string, require: list<array{courseCode: string, identifier: string, uri: string}>} $left
     * @param array{status: string, require: list<array{courseCode: string, identifier: string, uri: string}>} $right
     *
     * @return array{status: string, require: list<array{courseCode: string, identifier: string, uri: string}>}
     */
    private function mergePathOptions(array $left, array $right): array
    {
        return [
            'status' => $this->combineAllOfStatus($left['status'], $right['status']),
            'require' => array_merge($left['require'], $right['require']),
        ];
    }

    private function combineAllOfStatus(string $left, string $right): string
    {
        if ('satisfied' === $left && 'satisfied' === $right) {
            return 'satisfied';
        }

        if ('unsatisfied' === $left || 'unsatisfied' === $right) {
            return 'unsatisfied';
        }

        return 'partial';
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array{courseCode: string, identifier: string, uri: string}
     */
    private function courseRef(array $node): array
    {
        return [
            'courseCode' => $node['courseCode'] ?? '',
            'identifier' => $node['identifier'],
            'uri' => $node['uri'],
        ];
    }

    /**
     * @param array<string, mixed> $node
     */
    private function wouldExceedCap(array $node, int $cap): bool
    {
        return $this->countPathOptions($node) > $cap;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function countPathOptions(array $node, int $depth = 0): int
    {
        if ($depth > self::MAX_DEPTH) {
            return 0;
        }

        if (!isset($node['op'])) {
            return 1;
        }

        $counts = [];
        foreach ($node['members'] as $member) {
            $counts[] = $this->countPathOptions($member, $depth + 1);
        }

        if ('anyOf' === $node['op']) {
            return array_sum($counts);
        }

        $product = 1;
        foreach ($counts as $count) {
            $product *= $count;
        }

        return $product;
    }
}
