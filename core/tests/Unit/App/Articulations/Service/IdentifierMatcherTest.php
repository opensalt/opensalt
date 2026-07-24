<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Service;

use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Service\CourseCodeNormalizer;
use App\Articulations\Service\IdentifierMatcher;
use PHPUnit\Framework\TestCase;

final class IdentifierMatcherTest extends TestCase
{
    public function testFirstMatchWinsTriesAllTypes(): void
    {
        $matcher = new IdentifierMatcher(new CourseCodeNormalizer());
        $request = EntityIdentifiers::fromArray([
            ['type' => 'unknown:type', 'value' => 'X'],
            ['type' => 'courseCode', 'value' => 'MATH 10'],
            ['type' => 'identifier', 'value' => 'uuid-1'],
        ]);
        $known = [
            'courseCode' => ['MATH 10'],
            'identifier' => ['uuid-other'],
        ];

        $this->assertTrue($matcher->matches($request, $known));
    }

    public function testCourseCodeIgnoresWhitespace(): void
    {
        $matcher = new IdentifierMatcher(new CourseCodeNormalizer());
        $request = EntityIdentifiers::fromArray([
            ['type' => 'courseCode', 'value' => 'MATH10'],
        ]);
        $known = [
            'courseCode' => ['MATH 10'],
        ];

        $this->assertTrue($matcher->matches($request, $known));
    }

    public function testExtensionTypeMatchesExactly(): void
    {
        $matcher = new IdentifierMatcher(new CourseCodeNormalizer());
        $request = EntityIdentifiers::fromArray([
            ['type' => 'coci:schoolId', 'value' => '46'],
        ]);
        $known = [
            'coci:schoolId' => ['46'],
        ];

        $this->assertTrue($matcher->matches($request, $known));
    }

    public function testNoMatchReturnsFalse(): void
    {
        $matcher = new IdentifierMatcher(new CourseCodeNormalizer());
        $request = EntityIdentifiers::fromArray([
            ['type' => 'courseCode', 'value' => 'CHEM 1A'],
        ]);

        $this->assertFalse($matcher->matches($request, [
            'courseCode' => ['MATH 10'],
        ]));
    }

    public function testCourseCodeMatchesLeadingZerosAndTrailingPeriod(): void
    {
        $matcher = new IdentifierMatcher(new CourseCodeNormalizer());
        $request = EntityIdentifiers::fromArray([
            ['type' => 'courseCode', 'value' => 'MATH 12'],
        ]);
        $known = [
            'courseCode' => ['MATH 012.'],
        ];

        $this->assertTrue($matcher->matches($request, $known));
    }

    public function testCourseCodeDoesNotMatchDifferentNumbers(): void
    {
        $matcher = new IdentifierMatcher(new CourseCodeNormalizer());
        $request = EntityIdentifiers::fromArray([
            ['type' => 'courseCode', 'value' => 'MATH 10'],
        ]);

        $this->assertFalse($matcher->matches($request, [
            'courseCode' => ['MATH 100'],
        ]));
    }
}
