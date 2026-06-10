<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Scheduler\Task;

use App\Scheduler\Task\UpdateNextMirrorServer;
use App\Service\MirrorServer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

class UpdateNextMirrorServerTest extends TestCase
{
    private MirrorServer&MockObject $mirrorServer;
    private LoggerInterface&MockObject $logger;
    private LockFactory&MockObject $lockFactory;
    private SharedLockInterface&MockObject $lock;
    private UpdateNextMirrorServer $task;

    protected function setUp(): void
    {
        $this->mirrorServer = $this->createMock(MirrorServer::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->lock = $this->createMock(SharedLockInterface::class);
        $this->lockFactory = $this->createMock(LockFactory::class);
        $this->lockFactory->method('createLock')->willReturn($this->lock);

        $this->task = new UpdateNextMirrorServer(
            $this->mirrorServer,
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

        $this->mirrorServer->expects($this->once())
            ->method('updateNext');

        ($this->task)();
    }

    public function testSkipsWhenLockNotAcquired(): void
    {
        $this->lock->method('acquire')
            ->with(false)
            ->willReturn(false);

        $this->mirrorServer->expects($this->never())
            ->method('updateNext');

        ($this->task)();
    }

    public function testLogsErrorAndReleasesLockOnException(): void
    {
        $this->lock->method('acquire')
            ->with(false)
            ->willReturn(true);

        $exception = new \RuntimeException('Connection failed');
        $this->mirrorServer->method('updateNext')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Scheduler task failed',
                $this->callback(fn (array $ctx) =>
                    $ctx['task'] === UpdateNextMirrorServer::class
                    && $ctx['exception'] === $exception
                ),
            );

        $this->lock->expects($this->once())
            ->method('release');

        $this->expectException(\RuntimeException::class);
        ($this->task)();
    }
}
