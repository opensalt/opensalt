<?php

declare(strict_types=1);

namespace App\Articulations\Service;

use App\Entity\Framework\LsItem;

final class EntityIdentifierIndex
{
    /**
     * @return array<string, list<string>>
     */
    public function fromLsItem(LsItem $item): array
    {
        $known = [
            'identifier' => [$item->getIdentifier()],
        ];

        $uri = $item->getUri();
        if ('' !== $uri) {
            $known['uri'] = [$uri];
        }

        $courseCode = $item->getHumanCodingScheme();
        if (null !== $courseCode && '' !== $courseCode) {
            $known['courseCode'][] = $courseCode;
        }

        foreach ($item->getExtensions() as $key => $value) {
            if (!is_string($key) || '' === $key) {
                continue;
            }

            $scalar = $this->scalarExtensionValue($value);
            if (null !== $scalar) {
                $known[$key][] = $scalar;
            }
        }

        return $known;
    }

    private function scalarExtensionValue(mixed $value): ?string
    {
        if (is_string($value)) {
            return '' !== $value ? $value : null;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}
