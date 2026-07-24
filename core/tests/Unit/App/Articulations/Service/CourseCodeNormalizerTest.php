<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Service;

use App\Articulations\Service\CourseCodeNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CourseCodeNormalizerTest extends TestCase
{
    #[DataProvider('provideNormalizationCases')]
    public function testNormalize(string $input, string $expected): void
    {
        $this->assertSame($expected, (new CourseCodeNormalizer())->normalize($input));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideNormalizationCases(): iterable
    {
        yield 'chem spaced vs padded' => ['CHEM 12A', 'CHEM12A'];
        yield 'chem padded zeros' => ['CHEM 012A', 'CHEM12A'];
        yield 'cs spaced letters' => ['C S 110', 'CS110'];
        yield 'cs compact' => ['CS 110', 'CS110'];
        yield 'math letter suffix' => ['MATH 1C', 'MATH1C'];
        yield 'math padded letter' => ['MATH001C', 'MATH1C'];
        yield 'math trailing period' => ['MATH 012.', 'MATH12'];
        yield 'math plain' => ['MATH 12', 'MATH12'];
        yield 'trim' => ['  MATH 12  ', 'MATH12'];
        yield 'preserve lone zero run as zero' => ['MATH 0', 'MATH0'];
        yield 'preserve zero among digits carefully' => ['MATH 10', 'MATH10'];
    }
}
