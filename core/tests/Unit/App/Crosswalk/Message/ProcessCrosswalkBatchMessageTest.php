<?php

declare(strict_types=1);

namespace Tests\Unit\App\Crosswalk\Message;

use App\Crosswalk\Message\ProcessCrosswalkBatchMessage;
use PHPUnit\Framework\TestCase;

final class ProcessCrosswalkBatchMessageTest extends TestCase
{
    public function testConstructorAndPropertyAccess(): void
    {
        $message = new ProcessCrosswalkBatchMessage(
            jobId: '550e8400-e29b-41d4-a716-446655440000',
            itemIds: [1, 2, 3],
            destinationLeafOnly: true,
        );

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $message->jobId);
        $this->assertSame([1, 2, 3], $message->itemIds);
        $this->assertTrue($message->destinationLeafOnly);
    }

    public function testDefaults(): void
    {
        $message = new ProcessCrosswalkBatchMessage(
            jobId: 'test-id',
            itemIds: [42],
        );

        $this->assertSame('test-id', $message->jobId);
        $this->assertSame([42], $message->itemIds);
        $this->assertFalse($message->destinationLeafOnly);
    }
}
