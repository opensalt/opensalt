<?php

declare(strict_types=1);

namespace App\Crosswalk\Entity;

use App\Crosswalk\Repository\CrosswalkJobRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CrosswalkJobRepository::class)]
#[ORM\Table(name: 'crosswalk_job')]
#[ORM\Index(name: 'idx_crosswalk_job_status', columns: ['status'])]
#[ORM\Index(name: 'idx_crosswalk_job_crosswalk_framework', columns: ['crosswalk_framework_id'])]
class CrosswalkJob
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    public private(set) Uuid $id;

    #[ORM\Column(length: 20, options: ['default' => 'queued'])]
    public private(set) string $status = 'queued';

    #[ORM\Column]
    public private(set) int $originFrameworkId;

    #[ORM\Column]
    public private(set) int $destinationFrameworkId;

    #[ORM\Column]
    public private(set) int $crosswalkFrameworkId;

    #[ORM\Column(options: ['default' => 0.75])]
    public private(set) float $threshold = 0.75;

    #[ORM\Column(options: ['default' => 0.90])]
    public private(set) float $exactMatchThreshold = 0.90;

    #[ORM\Column(options: ['default' => 0])]
    public private(set) int $totalItems = 0;

    #[ORM\Column(options: ['default' => 0])]
    public private(set) int $processedItems = 0;

    #[ORM\Column(options: ['default' => 0])]
    public private(set) int $matchedItems = 0;

    #[ORM\Column(options: ['default' => 0])]
    public private(set) int $exactMatchItems = 0;

    #[ORM\Column(options: ['default' => 0])]
    public private(set) int $relatedItems = 0;

    #[ORM\Column(name: 'skipped_no_embedding', options: ['default' => 0])]
    public private(set) int $skippedNoEmbedding = 0;

    #[ORM\Column(name: 'skipped_below_threshold', options: ['default' => 0])]
    public private(set) int $skippedBelowThreshold = 0;

    #[ORM\Column(options: ['default' => 0])]
    public private(set) int $failedItems = 0;

    #[ORM\Column]
    public private(set) \DateTimeImmutable $queuedAt;

    #[ORM\Column(nullable: true)]
    public private(set) ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    public private(set) ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public private(set) ?string $errorMessage = null;

    public function __construct(
        int $originFrameworkId,
        int $destinationFrameworkId,
        int $crosswalkFrameworkId,
        float $threshold = 0.75,
        float $exactMatchThreshold = 0.90,
    ) {
        $this->id = Uuid::v7();
        $this->originFrameworkId = $originFrameworkId;
        $this->destinationFrameworkId = $destinationFrameworkId;
        $this->crosswalkFrameworkId = $crosswalkFrameworkId;
        $this->threshold = $threshold;
        $this->exactMatchThreshold = $exactMatchThreshold;
        $this->queuedAt = new \DateTimeImmutable();
    }

    public function markStarted(int $totalItems): void
    {
        $this->status = 'running';
        $this->totalItems = $totalItems;
        $this->startedAt = new \DateTimeImmutable();
    }

    public function recordItemProcessed(float $score, bool $isExactMatch): void
    {
        ++$this->processedItems;
        ++$this->matchedItems;
        if ($isExactMatch) {
            ++$this->exactMatchItems;
        } else {
            ++$this->relatedItems;
        }
    }

    public function recordItemNoEmbedding(): void
    {
        ++$this->processedItems;
        ++$this->skippedNoEmbedding;
    }

    public function recordItemBelowThreshold(): void
    {
        ++$this->processedItems;
        ++$this->skippedBelowThreshold;
    }

    public function recordItemFailed(): void
    {
        ++$this->processedItems;
        ++$this->failedItems;
    }

    public function markCompleted(): void
    {
        $this->status = 'completed';
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markPartial(string $errorMessage): void
    {
        $this->status = 'partial';
        $this->errorMessage = $errorMessage;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markFailed(string $errorMessage): void
    {
        $this->status = 'failed';
        $this->errorMessage = $errorMessage;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markCancelled(): void
    {
        $this->status = 'cancelled';
        $this->completedAt = new \DateTimeImmutable();
    }
}
