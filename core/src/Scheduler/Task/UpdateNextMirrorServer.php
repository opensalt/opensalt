<?php

declare(strict_types=1);

namespace App\Scheduler\Task;

use App\Service\MirrorServer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('* * * * *', jitter: 10)]
readonly class UpdateNextMirrorServer
{
    public function __construct(
        private MirrorServer $mirrorServer,
        private LoggerInterface $logger,
        private LockFactory $lockFactory,
    ) {
    }

    public function __invoke(): void
    {
        $lock = $this->lockFactory->createLock('update-next-mirror-server', ttl: 3600);
        if (!$lock->acquire(blocking: false)) {
            $this->logger->debug('Skipped {task}: lock held by previous run', [
                'task' => self::class,
            ]);

            return;
        }

        try {
            $this->mirrorServer->updateNext();
        } catch (\Throwable $e) {
            $this->logger->error('Scheduler task failed', [
                'task' => self::class,
                'exception' => $e,
            ]);
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
