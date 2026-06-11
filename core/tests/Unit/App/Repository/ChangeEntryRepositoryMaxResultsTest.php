<?php

declare(strict_types=1);

namespace Tests\Unit\App\Repository;

use App\Repository\ChangeEntryRepository;
use PHPUnit\Framework\TestCase;

class ChangeEntryRepositoryMaxResultsTest extends TestCase
{
    public function testMaxResultsConstantIsDefined(): void
    {
        $this->assertSame(50_000, ChangeEntryRepository::MAX_RESULTS);
    }

    public function testMaxResultsIsReasonableUpperBound(): void
    {
        $this->assertGreaterThan(0, ChangeEntryRepository::MAX_RESULTS);
        $this->assertLessThanOrEqual(100_000, ChangeEntryRepository::MAX_RESULTS);
    }
}
