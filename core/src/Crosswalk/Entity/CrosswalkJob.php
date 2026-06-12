<?php

declare(strict_types=1);

namespace App\Crosswalk\Entity;

use App\Crosswalk\Repository\CrosswalkJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CrosswalkJobRepository::class)]
#[ORM\Table(name: 'crosswalk_job')]
#[ORM\Index(name: 'idx_crosswalk_job_status', columns: ['status'])]
#[ORM\Index(name: 'idx_crosswalk_job_crosswalk_framework', columns: ['crosswalk_framework_id'])]
class CrosswalkJob
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID, unique: true)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'queued'])]
    private string $status = 'queued';

    #[ORM\Column(type: Types::INTEGER)]
    private int $originFrameworkId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $destinationFrameworkId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $crosswalkFrameworkId;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => 0.75])]
    private float $threshold = 0.75;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => 0.90])]
    private float $exactMatchThreshold = 0.90;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $totalItems = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $processedItems = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $matchedItems = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $exactMatchItems = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $relatedItems = 0;

    #[ORM\Column(name: 'skipped_no_embedding', type: Types::INTEGER, options: ['default' => 0])]
    private int $skippedNoEmbedding = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $failedItems = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $queuedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    public function __construct(
        int $originFrameworkId,
        int $destinationFrameworkId,
        int $crosswalkFrameworkId,
        float $threshold = 0.75,
        float $exactMatchThreshold = 0.90,
    ) {
        $this->id = uuid_create(UUID_TYPE_RANDOM);
        $this->originFrameworkId = $originFrameworkId;
        $this->destinationFrameworkId = $destinationFrameworkId;
        $this->crosswalkFrameworkId = $crosswalkFrameworkId;
        $this->threshold = $threshold;
        $this->exactMatchThreshold = $exactMatchThreshold;
        $this->queuedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getStatus(): string { return $this->status; }
    public function getOriginFrameworkId(): int { return $this->originFrameworkId; }
    public function getDestinationFrameworkId(): int { return $this->destinationFrameworkId; }
    public function getCrosswalkFrameworkId(): int { return $this->crosswalkFrameworkId; }
    public function getThreshold(): float { return $this->threshold; }
    public function getExactMatchThreshold(): float { return $this->exactMatchThreshold; }
    public function getTotalItems(): int { return $this->totalItems; }
    public function getProcessedItems(): int { return $this->processedItems; }
    public function getMatchedItems(): int { return $this->matchedItems; }
    public function getExactMatchItems(): int { return $this->exactMatchItems; }
    public function getRelatedItems(): int { return $this->relatedItems; }
    public function getSkippedNoEmbedding(): int { return $this->skippedNoEmbedding; }
    public function getFailedItems(): int { return $this->failedItems; }
    public function getQueuedAt(): \DateTimeImmutable { return $this->queuedAt; }
    public function getStartedAt(): ?\DateTimeImmutable { return $this->startedAt; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }

    public function markStarted(int $totalItems): void
    {
        $this->status = 'running';
        $this->totalItems = $totalItems;
        $this->startedAt = new \DateTimeImmutable();
    }

    public function recordItemProcessed(float $similarity, bool $isExactMatch): void
    {
        ++$this->processedItems;
        ++$this->matchedItems;
        if ($isExactMatch) {
            ++$this->exactMatchItems;
        } else {
            ++$this->relatedItems;
        }
    }

    public function recordItemSkipped(): void
    {
        ++$this->processedItems;
        ++$this->skippedNoEmbedding;
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
