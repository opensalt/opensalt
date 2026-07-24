<?php

declare(strict_types=1);

namespace App\Articulations\Service;

use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Model\Identifier;
use App\Entity\Framework\LsItem;

final class SendingCourseMatcher
{
    public function __construct(
        private readonly EntityIdentifierIndex $identifierIndex,
        private readonly IdentifierMatcher $identifierMatcher,
    ) {
    }

    /**
     * @param list<LsItem> $candidateCourses
     *
     * @return list<string>
     */
    public function matchIdentifiers(EntityIdentifiers $request, array $candidateCourses): array
    {
        foreach ($request->identifiers as $identifier) {
            $matched = $this->matchAllForType($identifier, $candidateCourses);
            if ([] !== $matched) {
                return array_values(array_unique($matched));
            }
        }

        return [];
    }

    /**
     * @param list<LsItem> $candidateCourses
     *
     * @return list<string>
     */
    private function matchAllForType(Identifier $identifier, array $candidateCourses): array
    {
        $matched = [];
        foreach ($candidateCourses as $course) {
            $known = $this->identifierIndex->fromLsItem($course);
            $single = EntityIdentifiers::fromArray([
                ['type' => $identifier->type, 'value' => $identifier->value],
            ]);
            if ($this->identifierMatcher->matches($single, $known)) {
                $matched[] = $course->getIdentifier();
            }
        }

        return $matched;
    }
}
