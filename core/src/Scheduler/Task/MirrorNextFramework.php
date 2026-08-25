<?php

declare(strict_types=1);

namespace App\Scheduler\Task;

use App\Service\MirrorFramework;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('* * * * *', jitter: 10)]
readonly class MirrorNextFramework
{
    public function __construct(
        private MirrorFramework $mirrorFramework,
        private LoggerInterface $logger,
        private LockFactory $lockFactory,
    ) {
    }

    public function __invoke(): void
    {
        $lock = $this->lockFactory->createLock('mirror-next-framework', ttl: 3600);
        if (!$lock->acquire(blocking: false)) {
            $this->logger->debug('Skipped {task}: lock held by previous run', [
                'task' => self::class,
            ]);

            return;
        }

        try {
            $this->mirrorFramework->mirrorNext();
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
