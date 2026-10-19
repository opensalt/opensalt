<?php

declare(strict_types=1);

namespace App\Service\Markdown;

final class PackageTreeBuilder
{
    /**
     * Order the light item rows into a stable hierarchy with section numbers.
     *
     * @param array<int, array{identifier: string, title: ?string, code: ?string, listEnum: ?string}> $tocRows
     * @param array<string, ?string> $parentMap
     *
     * @return array<int, array{identifier: string, number: string, depth: int, title: string, code: ?string}>
     */
    public function order(array $tocRows, array $parentMap): array
    {
        $byId = [];
        foreach ($tocRows as $row) {
            $byId[$row['identifier']] = $row;
        }

        // children[parentIdentifier] = list of child identifiers; roots live under ''
        $children = [];
        foreach ($byId as $id => $row) {
            $parent = $parentMap[$id] ?? null;
            $parent = (null !== $parent && isset($byId[$parent])) ? $parent : null;
            $children[$parent ?? ''][] = $id;
        }

        $ordered = [];
        $visited = [];

        $emit = function (string $parentId, int $depth, string $numberPrefix) use (&$emit, &$ordered, &$visited, $byId, $children): void {
            $ids = $children[$parentId] ?? [];
            usort($ids, static fn (string $a, string $b): int => self::compareSiblings($byId[$a], $byId[$b]));
            foreach (array_values($ids) as $index => $id) {
                if (isset($visited[$id])) {
                    continue; // cycle guard
                }
                $visited[$id] = true;
                $number = ('' === $numberPrefix) ? (string) ($index + 1) : $numberPrefix.'.'.($index + 1);
                $row = $byId[$id];
                $ordered[] = [
                    'identifier' => $id,
                    'number' => $number,
                    'depth' => $depth,
                    'title' => $row['title'] ?? $row['code'] ?? $id,
                    'code' => $row['code'],
                ];
                $emit($id, $depth + 1, $number);
            }
        };

        $emit('', 0, '');

        // Cycle safety: items unreachable from any root are appended, top level,
        // in input order, so nothing in the framework is silently dropped.
        foreach ($tocRows as $row) {
            $id = $row['identifier'];
            if (isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;
            $topLevelCount = count(array_filter($ordered, static fn (array $e) => 0 === $e['depth']));
            $ordered[] = [
                'identifier' => $id,
                'number' => (string) ($topLevelCount + 1),
                'depth' => 0,
                'title' => $row['title'] ?? $row['code'] ?? $id,
                'code' => $row['code'],
            ];
        }

        return $ordered;
    }

    /**
     * @param array{identifier: string, title: ?string, code: ?string, listEnum: ?string} $a
     * @param array{identifier: string, title: ?string, code: ?string, listEnum: ?string} $b
     */
    private static function compareSiblings(array $a, array $b): int
    {
        $enumA = (int) ($a['listEnum'] ?? PHP_INT_MAX);
        $enumB = (int) ($b['listEnum'] ?? PHP_INT_MAX);
        if ($enumA !== $enumB) {
            return $enumA <=> $enumB;
        }

        return strnatcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
    }
}
