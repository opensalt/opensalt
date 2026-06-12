<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\Message;

use App\Crosswalk\Message\CreateCrosswalkMessage;
use PHPUnit\Framework\TestCase;

final class CreateCrosswalkMessageTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $message = new CreateCrosswalkMessage(
            jobId: '550e8400-e29b-41d4-a716-446655440000',
            originFrameworkId: 42,
            destinationFrameworkId: 87,
            crosswalkFrameworkId: 156,
            threshold: 0.75,
            exactMatchThreshold: 0.90,
        );

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $message->getJobId());
        $this->assertSame(42, $message->getOriginFrameworkId());
        $this->assertSame(87, $message->getDestinationFrameworkId());
        $this->assertSame(156, $message->getCrosswalkFrameworkId());
        $this->assertSame(0.75, $message->getThreshold());
        $this->assertSame(0.90, $message->getExactMatchThreshold());
    }

    public function testCustomThresholds(): void
    {
        $message = new CreateCrosswalkMessage(
            jobId: 'test-id',
            originFrameworkId: 1,
            destinationFrameworkId: 2,
            crosswalkFrameworkId: 3,
            threshold: 0.60,
            exactMatchThreshold: 0.85,
        );

        $this->assertSame(0.60, $message->getThreshold());
        $this->assertSame(0.85, $message->getExactMatchThreshold());
    }
}
