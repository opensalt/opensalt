<?php

declare(strict_types=1);

namespace Tests\Unit\App\Repository\Framework;

use App\Repository\Framework\LsDocRepository;
use PHPUnit\Framework\TestCase;

class LsDocRepositoryMaxResultsTest extends TestCase
{
    public function testMaxResultsConstantIsDefined(): void
    {
        $this->assertSame(50_000, LsDocRepository::MAX_RESULTS);
    }

    public function testMaxResultsConstantIsPositive(): void
    {
        $this->assertGreaterThan(0, LsDocRepository::MAX_RESULTS);
    }

    public function testMaxResultsIsReasonableUpperBound(): void
    {
        $this->assertLessThanOrEqual(100_000, LsDocRepository::MAX_RESULTS);
        $this->assertGreaterThanOrEqual(10_000, LsDocRepository::MAX_RESULTS);
    }
}
