<?php

declare(strict_types=1);

namespace App\Messenger\Retry;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

readonly class LowPriorityRetryStrategy implements RetryStrategyInterface
{
    public function __construct(
        private int $maxRetries = 3,
        private int $delayMultiplier = 5,
    ) {
    }

    #[\Override]
    public function isRetryable(Envelope $message, ?\Throwable $throwable = null): bool
    {
        $retryCount = RedeliveryStamp::getRetryCountFromEnvelope($message);

        return $retryCount < $this->maxRetries;
    }

    #[\Override]
    public function getWaitingTime(Envelope $message, ?\Throwable $throwable = null): int
    {
        $retryCount = RedeliveryStamp::getRetryCountFromEnvelope($message);
        $base = (int) ($this->delayMultiplier ** ($retryCount + 1) * 1000);
        $jitter = random_int(0, (int) ($base * 0.1));

        return $base + $jitter;
    }
}
