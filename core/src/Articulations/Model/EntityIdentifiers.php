<?php

declare(strict_types=1);

namespace App\Articulations\Model;

final readonly class EntityIdentifiers
{
    /** @param list<Identifier> $identifiers */
    public function __construct(public array $identifiers)
    {
    }

    /**
     * @param list<array{type?: mixed, value?: mixed}> $raw
     */
    public static function fromArray(array $raw): self
    {
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = isset($row['type']) ? trim((string) $row['type']) : '';
            $value = isset($row['value']) ? (string) $row['value'] : '';
            if ('' === $type || '' === $value) {
                continue;
            }
            $out[] = new Identifier($type, $value);
        }

        return new self($out);
    }
}
