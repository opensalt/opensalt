<?php

declare(strict_types=1);

namespace App\Crosswalk\Message;

readonly class CreateCrosswalkMessage
{
    public function __construct(
        public string $jobId,
        public int $originFrameworkId,
        public int $destinationFrameworkId,
        public int $crosswalkFrameworkId,
        public float $threshold = 0.75,
        public float $exactMatchThreshold = 0.90,
        public bool $originLeafOnly = false,
        public bool $destinationLeafOnly = false,
    ) {
    }
}
