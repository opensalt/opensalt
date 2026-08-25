<?php

declare(strict_types=1);

namespace App\Articulations\Service;

use App\Articulations\Model\EntityIdentifiers;

final readonly class IdentifierMatcher
{
    public function __construct(
        private CourseCodeNormalizer $courseCodeNormalizer = new CourseCodeNormalizer(),
    ) {
    }

    /**
     * @param array<string, list<string>> $knownByType
     */
    public function matches(EntityIdentifiers $request, array $knownByType): bool
    {
        foreach ($request->identifiers as $id) {
            $candidates = $knownByType[$id->type] ?? [];
            foreach ($candidates as $candidate) {
                if ($this->valuesEqual($id->type, $id->value, $candidate)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function valuesEqual(string $type, string $requestValue, string $knownValue): bool
    {
        if ('courseCode' === $type) {
            return $this->courseCodeNormalizer->normalize($requestValue)
                === $this->courseCodeNormalizer->normalize($knownValue);
        }

        return $requestValue === $knownValue;
    }
}
