<?php

declare(strict_types=1);

namespace App\Crosswalk\Message;

readonly class CreateCrosswalkMessage
{
    public function __construct(
        private string $jobId,
        private int $originFrameworkId,
        private int $destinationFrameworkId,
        private int $crosswalkFrameworkId,
        private float $threshold = 0.75,
        private float $exactMatchThreshold = 0.90,
    ) {
    }

    public function getJobId(): string { return $this->jobId; }
    public function getOriginFrameworkId(): int { return $this->originFrameworkId; }
    public function getDestinationFrameworkId(): int { return $this->destinationFrameworkId; }
    public function getCrosswalkFrameworkId(): int { return $this->crosswalkFrameworkId; }
    public function getThreshold(): float { return $this->threshold; }
    public function getExactMatchThreshold(): float { return $this->exactMatchThreshold; }
}
