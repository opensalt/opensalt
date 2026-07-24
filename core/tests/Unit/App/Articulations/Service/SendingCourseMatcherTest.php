<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Service;

use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Service\CourseCodeNormalizer;
use App\Articulations\Service\EntityIdentifierIndex;
use App\Articulations\Service\IdentifierMatcher;
use App\Articulations\Service\SendingCourseMatcher;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use PHPUnit\Framework\TestCase;

final class SendingCourseMatcherTest extends TestCase
{
    public function testCourseCodeMarksAllNormalizedMatches(): void
    {
        $courses = [
            $this->course('id-a', 'CHEM 012A'),
            $this->course('id-b', 'CHEM 012A'),
            $this->course('id-c', 'MATH 012.'),
        ];

        $matcher = $this->createMatcher();
        $request = EntityIdentifiers::fromArray([
            ['type' => 'courseCode', 'value' => 'CHEM 12A'],
        ]);

        $matched = $matcher->matchIdentifiers($request, $courses);

        $this->assertEqualsCanonicalizing(['id-a', 'id-b'], $matched);
    }

    public function testFirstIdentifierTypeWins(): void
    {
        $courses = [
            $this->course('id-a', 'MATH 012.', uri: 'local:a'),
            $this->course('id-b', 'MATH 012.', uri: 'local:b'),
        ];

        $matcher = $this->createMatcher();
        $request = EntityIdentifiers::fromArray([
            ['type' => 'identifier', 'value' => 'id-a'],
            ['type' => 'courseCode', 'value' => 'MATH 12'],
        ]);

        $this->assertSame(['id-a'], $matcher->matchIdentifiers($request, $courses));
    }

    public function testExtensionMatchWorks(): void
    {
        $course = $this->course('id-a', 'MATH 012.', extensions: ['coci:courseId' => '123']);
        $matcher = $this->createMatcher();
        $request = EntityIdentifiers::fromArray([
            ['type' => 'coci:courseId', 'value' => '123'],
        ]);

        $this->assertSame(['id-a'], $matcher->matchIdentifiers($request, [$course]));
    }

    public function testNoMatchReturnsEmpty(): void
    {
        $matcher = $this->createMatcher();
        $request = EntityIdentifiers::fromArray([
            ['type' => 'courseCode', 'value' => 'MISSING 1'],
        ]);

        $this->assertSame([], $matcher->matchIdentifiers($request, [
            $this->course('id-a', 'MATH 012.'),
        ]));
    }

    private function createMatcher(): SendingCourseMatcher
    {
        return new SendingCourseMatcher(
            new EntityIdentifierIndex(),
            new IdentifierMatcher(new CourseCodeNormalizer()),
        );
    }

    /**
     * @param array<string, string> $extensions
     */
    private function course(
        string $identifier,
        string $courseCode,
        string $uri = '',
        array $extensions = [],
    ): LsItem {
        $item = $this->createMock(LsItem::class);
        $item->method('getIdentifier')->willReturn($identifier);
        $item->method('getUri')->willReturn('' !== $uri ? $uri : 'local:'.$identifier);
        $item->method('getHumanCodingScheme')->willReturn($courseCode);
        $item->method('getExtensions')->willReturn($extensions);
        $item->method('getDiscriminator')->willReturn(LsItemKind::Course->value);

        return $item;
    }
}
