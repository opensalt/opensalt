<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\Entity;

use App\Crosswalk\Entity\CrosswalkJob;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

final class CrosswalkJobTest extends TestCase
{
    public function testCreateWithDefaults(): void
    {
        $job = new CrosswalkJob(
            originFrameworkId: 42,
            destinationFrameworkId: 87,
            crosswalkFrameworkId: 156,
        );

        $this->assertInstanceOf(UuidInterface::class, $job->id);
        $this->assertSame('queued', $job->status);
        $this->assertSame(0.75, $job->threshold);
        $this->assertSame(0.90, $job->exactMatchThreshold);
        $this->assertSame(0, $job->processedItems);
        $this->assertSame(0, $job->matchedItems);
        $this->assertNotNull($job->queuedAt);
        $this->assertNull($job->startedAt);
        $this->assertNull($job->completedAt);
    }

    public function testMarkStarted(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(1832);

        $this->assertSame('running', $job->status);
        $this->assertSame(1832, $job->totalItems);
        $this->assertNotNull($job->startedAt);
    }

    public function testRecordItemProcessedWithExactMatch(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemProcessed(score: 0.95, isExactMatch: true);

        $this->assertSame(1, $job->processedItems);
        $this->assertSame(1, $job->matchedItems);
        $this->assertSame(1, $job->exactMatchItems);
        $this->assertSame(0, $job->relatedItems);
    }

    public function testRecordItemProcessedWithRelatedMatch(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemProcessed(score: 0.80, isExactMatch: false);

        $this->assertSame(1, $job->processedItems);
        $this->assertSame(1, $job->matchedItems);
        $this->assertSame(0, $job->exactMatchItems);
        $this->assertSame(1, $job->relatedItems);
    }

    public function testRecordItemNoEmbedding(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemNoEmbedding();

        $this->assertSame(1, $job->processedItems);
        $this->assertSame(0, $job->matchedItems);
        $this->assertSame(1, $job->skippedNoEmbedding);
        $this->assertSame(0, $job->skippedBelowThreshold);
    }

    public function testRecordItemBelowThreshold(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemBelowThreshold();

        $this->assertSame(1, $job->processedItems);
        $this->assertSame(0, $job->matchedItems);
        $this->assertSame(0, $job->skippedNoEmbedding);
        $this->assertSame(1, $job->skippedBelowThreshold);
    }

    public function testRecordItemFailed(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemFailed();

        $this->assertSame(1, $job->processedItems);
        $this->assertSame(1, $job->failedItems);
    }

    public function testMarkCompleted(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->markCompleted();

        $this->assertSame('completed', $job->status);
        $this->assertNotNull($job->completedAt);
    }

    public function testMarkPartial(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->recordItemFailed();
        $job->markPartial('Some items failed');

        $this->assertSame('partial', $job->status);
        $this->assertSame('Some items failed', $job->errorMessage);
        $this->assertNotNull($job->completedAt);
    }

    public function testMarkCancelled(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $job->markStarted(100);
        $job->markCancelled();

        $this->assertSame('cancelled', $job->status);
        $this->assertNotNull($job->completedAt);
    }

    public function testDefaultThresholds(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $this->assertSame(0.75, $job->threshold);
        $this->assertSame(0.90, $job->exactMatchThreshold);
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
        $this->assertSame(0.60, $job->threshold);
        $this->assertSame(0.85, $job->exactMatchThreshold);
    }

    public function testIdIsStringable(): void
    {
        $job = new CrosswalkJob(42, 87, 156);
        $idString = (string) $job->id;

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $idString,
        );
    }
}
