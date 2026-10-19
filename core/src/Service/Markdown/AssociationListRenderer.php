<?php

declare(strict_types=1);

namespace App\Service\Markdown;

/**
 * Renders association rows (from AssociationRowsCollector) as markdown lists
 * grouped under a heading per association type, sub-grouped by the framework
 * each target lives in, with extra association data as nested bullets.
 * Entries sort by curricular order: sequence number, then the target's
 * list enumeration, then title.
 */
final readonly class AssociationListRenderer
{
    /** Association types whose inverse maps onto themselves. */
    private const array SYMMETRIC_TYPES = [
        'exactMatchOf' => true,
        'isRelatedTo' => true,
        'isPeerOf' => true,
    ];

    public function __construct(
        private HtmlToMarkdownConverter $htmlConverter,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function render(array $rows, int $offset = 0): string
    {
        if ([] === $rows) {
            return '';
        }

        $byType = [];
        foreach ($rows as $row) {
            $byType[(string) $row['relationship']][] = $row;
        }
        ksort($byType);

        $blocks = [];
        foreach ($byType as $type => $typeRows) {
            $blocks[] = $this->typeBlock($type, $typeRows, $offset);
        }

        return MarkdownBuilder::joinBlocks($blocks);
    }

    /**
     * Entries grouped under a heading per framework (frameworkless external
     * targets stay flat, last); groups and entries sort deterministically.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private function typeBlock(string $type, array $rows, int $offset): string
    {
        $blocks = [MarkdownBuilder::h(3 + $offset, $this->typeHeading($type, $rows))];
        $currentFramework = null;
        $lines = [];

        $sorted = $rows;
        usort($sorted, function (array $a, array $b): int {
            $fa = self::frameworkOf($a);
            $fb = self::frameworkOf($b);
            $cmp = [null === $fa ? 1 : 0, $fa ?? ''] <=> [null === $fb ? 1 : 0, $fb ?? ''];
            if (0 !== $cmp) {
                return $cmp;
            }

            return self::curricularCompare($a, $b);
        });

        foreach ($sorted as $row) {
            $framework = self::frameworkOf($row);
            if ($framework !== $currentFramework) {
                if ([] !== $lines) {
                    $blocks[] = implode("\n", $lines);
                    $lines = [];
                }
                if (null !== $framework) {
                    $blocks[] = MarkdownBuilder::h(4 + $offset, MarkdownBuilder::link($framework, (string) ($row['frameworkUri'] ?? '')));
                }
                $currentFramework = $framework;
            }
            $lines[] = $this->listItem($row);
        }
        if ([] !== $lines) {
            $blocks[] = implode("\n", $lines);
        }

        return MarkdownBuilder::joinBlocks($blocks);
    }

    /**
     * Curricular order: the association's sequence number, then the target's
     * list enumeration (natural sort), then title.  Entries missing a value
     * sort last at that level.
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private static function curricularCompare(array $a, array $b): int
    {
        $seqA = isset($a['sequenceNumber']) && null !== $a['sequenceNumber'] ? (int) $a['sequenceNumber'] : PHP_INT_MAX;
        $seqB = isset($b['sequenceNumber']) && null !== $b['sequenceNumber'] ? (int) $b['sequenceNumber'] : PHP_INT_MAX;
        if ($seqA !== $seqB) {
            return $seqA <=> $seqB;
        }

        $enumA = isset($a['listEnumeration']) && null !== $a['listEnumeration'] ? (string) $a['listEnumeration'] : '';
        $enumB = isset($b['listEnumeration']) && null !== $b['listEnumeration'] ? (string) $b['listEnumeration'] : '';
        if ('' === $enumA && '' !== $enumB) {
            return 1;
        }
        if ('' === $enumB && '' !== $enumA) {
            return -1;
        }
        $byEnum = strnatcmp($enumA, $enumB);
        if (0 !== $byEnum) {
            return $byEnum;
        }

        return strnatcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
    }

    /**
     * Incoming rows are shown under the inverted relationship name; say so,
     * so the stored type stays discoverable.  Symmetric types need no note.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private function typeHeading(string $type, array $rows): string
    {
        $incoming = array_filter($rows, static fn (array $r) => 'incoming' === ($r['direction'] ?? ''));
        if ([] === $incoming || count($incoming) !== count($rows)) {
            return $type;
        }
        if (isset(self::SYMMETRIC_TYPES[$type])) {
            return $type.' (symmetric, incoming)';
        }

        return $type.' (incoming)';
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function frameworkOf(array $row): ?string
    {
        $framework = $row['framework'] ?? null;

        return (is_string($framework) && '' !== $framework) ? $framework : null;
    }

    /**
     * Curricular order: the association's sequence number, then the target's
     * list enumeration, then title.
     *
     * @param array<string, mixed> $row
     */
    private static function curricularOrder(array $row): array
    {
        $seq = $row['sequenceNumber'] ?? null;
        $enum = $row['listEnumeration'] ?? null;

        return [
            null === $seq ? PHP_INT_MAX : (int) $seq,
            strnatcmp((string) $enum, (string) $enum),
            (string) ($enum ?? ''),
            (string) ($row['title'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function listItem(array $row): string
    {
        $uri = (string) ($row['uri'] ?? '');
        $unresolved = isset($row['unresolved']) && null !== $row['unresolved'] ? (string) $row['unresolved'] : null;
        $title = (string) ($row['title'] ?? '');
        $code = $row['code'] ?? null;
        if (is_string($code) && '' !== $code && $code !== $title) {
            $title .= ' ('.$code.')';
        }

        if (null !== $unresolved) {
            // The target cannot be resolved locally; identify what is missing
            // instead of showing a bare identifier.
            $target = $title.' '.MarkdownBuilder::inlineCode($unresolved);
        } elseif ('1' === ($row['local'] ?? '') && '' !== $uri) {
            $target = MarkdownBuilder::link($title, $uri);
        } else {
            $target = $uri;
        }

        $line = '- '.$target;

        $extra = [
            'association group' => $row['group'] ?? null,
            'subtype' => $row['subtype'] ?? null,
            'notes' => $this->htmlConverter->convert(isset($row['notes']) && is_string($row['notes']) ? $row['notes'] : null),
        ];
        foreach ($extra as $label => $value) {
            if (null === $value || '' === $value) {
                continue;
            }
            $line .= "\n  - {$label}: {$value}";
        }

        $extensions = is_array($row['extensions'] ?? null) ? $row['extensions'] : [];
        foreach ($extensions as $key => $value) {
            if (null === $value) {
                continue;
            }
            $line .= "\n  - ".MarkdownBuilder::inlineCode((string) $key).': '.$this->extensionValue($value);
        }

        return $line;
    }

    private function extensionValue(mixed $value): string
    {
        if (is_scalar($value) || null === $value) {
            return (string) $value;
        }

        return MarkdownBuilder::inlineCode((string) json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
