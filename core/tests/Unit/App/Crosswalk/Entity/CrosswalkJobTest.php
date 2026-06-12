<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\Entity;

use App\Crosswalk\Entity\CrosswalkJob;
use PHPUnit\Framework\TestCase;

final class CrosswalkJobTest extends TestCase
{
    public function testCreateWithDefaults(): void
    {
        $job = new CrosswalkJob(
            originFrameworkId: 42,
            destinationFrameworkId: 87,
            crosswalkFrameworkId: 156,
        );

        $this->assertSame('queued', $job->getStatus());
        $this->assertSame(0.75, $job->getThreshold());
        $this->assertSame(0.90, $job->getExactMatchThreshold());
        $this->assertSame(0, $job->getProcessedItems());
        $this->assertSame(0, $job->getMatchedItems());
        $this->assertNotNull($job->getQueuedAt());
        $this->assertNull($job->getStartedAt());
        $this->assertNull($job->getCompletedAt());
    }

    public function testMarkStarted(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(1832);

        $this->assertSame('running', $job->getStatus());
        $this->assertSame(1832, $job->getTotalItems());
        $this->assertNotNull($job->getStartedAt());
    }

    public function testRecordItemProcessedWithExactMatch(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemProcessed(score: 0.95, isExactMatch: true);

        $this->assertSame(1, $job->getProcessedItems());
        $this->assertSame(1, $job->getMatchedItems());
        $this->assertSame(1, $job->getExactMatchItems());
        $this->assertSame(0, $job->getRelatedItems());
    }

    public function testRecordItemProcessedWithRelatedMatch(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemProcessed(score: 0.80, isExactMatch: false);

        $this->assertSame(1, $job->getProcessedItems());
        $this->assertSame(1, $job->getMatchedItems());
        $this->assertSame(0, $job->getExactMatchItems());
        $this->assertSame(1, $job->getRelatedItems());
    }

    public function testRecordItemSkipped(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemSkipped();

        $this->assertSame(1, $job->getProcessedItems());
        $this->assertSame(0, $job->getMatchedItems());
        $this->assertSame(1, $job->getSkippedNoEmbedding());
    }

    public function testRecordItemFailed(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemFailed();

        $this->assertSame(1, $job->getProcessedItems());
        $this->assertSame(1, $job->getFailedItems());
    }

    public function testMarkCompleted(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->markCompleted();

        $this->assertSame('completed', $job->getStatus());
        $this->assertNotNull($job->getCompletedAt());
    }

    public function testMarkPartial(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemFailed();
        $job->markPartial('Some items failed');

        $this->assertSame('partial', $job->getStatus());
        $this->assertSame('Some items failed', $job->getErrorMessage());
        $this->assertNotNull($job->getCompletedAt());
    }

    public function testMarkCancelled(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->markCancelled();

        $this->assertSame('cancelled', $job->getStatus());
        $this->assertNotNull($job->getCompletedAt());
    }

    public function testDefaultThresholds(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $this->assertSame(0.75, $job->getThreshold());
        $this->assertSame(0.90, $job->getExactMatchThreshold());
    }

    public function testCustomThresholds(): void
    {
        $job = new CrosswalkJob(
            originFrameworkId: 42,
            destinationFrameworkId: 87,
            crosswalkFrameworkId: 156,
            threshold: 0.60,
            exactMatchThreshold: 0.85,
        );
        $this->assertSame(0.60, $job->getThreshold());
        $this->assertSame(0.85, $job->getExactMatchThreshold());
    }
}
