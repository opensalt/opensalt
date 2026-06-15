<?php

declare(strict_types=1);

namespace App\Crosswalk\Message;

readonly class ProcessCrosswalkBatchMessage
{
    /**
     * @param list<int> $itemIds
     */
    public function __construct(
        public string $jobId,
        public array $itemIds,
        public bool $destinationLeafOnly = false,
    ) {
    }
}
