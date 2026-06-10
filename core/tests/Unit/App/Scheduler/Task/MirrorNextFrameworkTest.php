<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Scheduler\Task;

use App\Scheduler\Task\MirrorNextFramework;
use App\Service\MirrorFramework;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

class MirrorNextFrameworkTest extends TestCase
{
    private MirrorFramework&MockObject $mirrorFramework;
    private LoggerInterface&MockObject $logger;
    private LockFactory&MockObject $lockFactory;
    private SharedLockInterface&MockObject $lock;
    private MirrorNextFramework $task;

    protected function setUp(): void
    {
        $this->mirrorFramework = $this->createMock(MirrorFramework::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->lock = $this->createMock(SharedLockInterface::class);
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->lockFactory->method('createLock')->willReturn($this->lock);

        $this->task = new MirrorNextFramework(
            $this->mirrorFramework,
            $this->logger,
            $this->lockFactory,
        );
    }

    public function testAcquiresLockAndRuns(): void
    {
        $this->lock->expects($this->once())
            ->method('acquire')
            ->with(false)
            ->willReturn(true);

        $this->mirrorFramework->expects($this->once())
            ->method('mirrorNext');

        $this->lock->expects($this->once())
            ->method('release');

        ($this->task)();
    }

    public function testSkipsWhenLockNotAcquired(): void
    {
        $this->lock->method('acquire')
            ->with(false)
            ->willReturn(false);

        $this->mirrorFramework->expects($this->never())
            ->method('mirrorNext');

        ($this->task)();
    }

    public function testLogsErrorAndReleasesLockOnException(): void
    {
        $this->lock->method('acquire')
            ->with(false)
            ->willReturn(true);

        $exception = new \RuntimeException('Service failure');
        $this->mirrorFramework->method('mirrorNext')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Scheduler task failed',
                $this->callback(fn (array $ctx) =>
                    $ctx['task'] === MirrorNextFramework::class
                    && $ctx['exception'] === $exception
                ),
            );

        $this->lock->expects($this->once())
            ->method('release');

        $this->expectException(\RuntimeException::class);
        ($this->task)();
    }

    public function testReleasesLockOnSuccess(): void
    {
        $this->lock->method('acquire')
            ->with(false)
            ->willReturn(true);

        $this->lock->expects($this->once())
            ->method('release');

        ($this->task)();
    }
}
