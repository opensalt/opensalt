<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Messenger\Retry;

use App\Messenger\Retry\LowPriorityRetryStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class LowPriorityRetryStrategyTest extends TestCase
{
    private LowPriorityRetryStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new LowPriorityRetryStrategy(
            maxRetries: 3,
            delayMultiplier: 5,
        );
    }

    public function testIsRetryableReturnsTrueByDefault(): void
    {
        $envelope = new Envelope(new \stdClass());

        $this->assertTrue($this->strategy->isRetryable($envelope));
    }

    public function testIsRetryableReturnsFalseAfterMaxRetries(): void
    {
        $envelope = new Envelope(new \stdClass(), [
            new RedeliveryStamp(1),
            new RedeliveryStamp(2),
            new RedeliveryStamp(3),
        ]);

        $this->assertFalse($this->strategy->isRetryable($envelope));
    }

    public function testGetWaitingTimeReturnsExponentialBackoff(): void
    {
        $envelope = new Envelope(new \stdClass());

        $time1 = $this->strategy->getWaitingTime($envelope);
        // Base is 5^1 * 1000 = 5000, with 0-10% jitter (0-500)
        $this->assertGreaterThanOrEqual(5000, $time1);
        $this->assertLessThanOrEqual(5500, $time1);

        $envelope2 = new Envelope(new \stdClass(), [new RedeliveryStamp(1)]);
        $time2 = $this->strategy->getWaitingTime($envelope2);
        // Base is 5^2 * 1000 = 25000, with 0-10% jitter (0-2500)
        $this->assertGreaterThanOrEqual(25000, $time2);
        $this->assertLessThanOrEqual(27500, $time2);

        $envelope3 = new Envelope(new \stdClass(), [new RedeliveryStamp(2)]);
        $time3 = $this->strategy->getWaitingTime($envelope3);
        // Base is 5^3 * 1000 = 125000, with 0-10% jitter (0-12500)
        $this->assertGreaterThanOrEqual(125000, $time3);
        $this->assertLessThanOrEqual(137500, $time3);
    }

    public function testMaxRetriesIs3(): void
    {
        $envelope = new Envelope(new \stdClass(), [
            new RedeliveryStamp(3),
        ]);

        $this->assertFalse($this->strategy->isRetryable($envelope));
    }

    public function testStillRetryableAt2Retries(): void
    {
        $envelope = new Envelope(new \stdClass(), [
            new RedeliveryStamp(2),
        ]);

        $this->assertTrue($this->strategy->isRetryable($envelope));
    }

    public function testIsRetryableAt0Retries(): void
    {
        $envelope = new Envelope(new \stdClass(), [
            new RedeliveryStamp(0),
        ]);

        $this->assertTrue($this->strategy->isRetryable($envelope));
    }
}
