<?php

declare(strict_types=1);

namespace App\Crosswalk\MessageHandler;

use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\ProcessCrosswalkBatchMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ProcessCrosswalkBatchMessageHandler
{
    /**
     * Number of items processed before flushing to the database.
     * Each flush is a short transaction that releases row locks
     * immediately, preventing long-held locks that would block
     * concurrent editor operations.
     */
    private const FLUSH_INTERVAL = 5;

    public function __construct(
        private CrosswalkJobRepository $jobRepository,
        private CrosswalkService $crosswalkService,
        private EntityManagerInterface $entityManager,
        private HubInterface $hub,
    ) {
    }

    public function __invoke(ProcessCrosswalkBatchMessage $message): void
    {
        $job = $this->jobRepository->find($message->jobId);
        if (null === $job) {
            return;
        }
        if ('cancelled' === $job->status) {
            return;
        }

        $sourceItems = $this->entityManager->getRepository(LsItem::class)->findBy([
            'id' => $message->itemIds,
        ]);

        $count = 0;
        foreach ($sourceItems as $sourceItem) {
            if ($count > 0 && 0 === $count % self::FLUSH_INTERVAL) {
                $this->jobRepository->save($job);
                $this->publishProgress($job);
                if ($this->isJobCancelled($message->jobId)) {
                    return;
                }
            }

            try {
                $match = $this->crosswalkService->findBestMatch(
                    $sourceItem,
                    $job->destinationFrameworkId,
                    $message->destinationLeafOnly,
                );

                if (null === $match) {
                    $job->recordItemNoEmbedding();
                } else {
                    $result = $this->crosswalkService->processItem(
                        $sourceItem,
                        $match['lsItem'],
                        $match['similarity'],
                        $job->exactMatchThreshold,
                        $job->crosswalkFrameworkId,
                        $job->threshold,
                        $message->jobId,
                    );

                    if (CrosswalkService::RESULT_CREATED_EXACT === $result) {
                        $job->recordItemProcessed($match['similarity'], true);
                    } elseif (CrosswalkService::RESULT_CREATED_RELATED === $result) {
                        $job->recordItemProcessed($match['similarity'], false);
                    } elseif (CrosswalkService::RESULT_SKIPPED_DUPLICATE === $result) {
                        $isExact = $match['similarity'] >= $job->exactMatchThreshold;
                        $job->recordItemProcessed($match['similarity'], $isExact);
                    } elseif (CrosswalkService::RESULT_SKIPPED_BELOW_THRESHOLD === $result) {
                        $job->recordItemBelowThreshold();
                    }
                }
            } catch (\Throwable) {
                $job->recordItemFailed();
            }

            ++$count;
        }

        $this->jobRepository->save($job);
        $this->publishProgress($job);

        if ($this->isJobCancelled($message->jobId)) {
            return;
        }

        if ($job->processedItems >= $job->totalItems && 'running' === $job->status) {
            $job->markCompleted();
            $this->jobRepository->save($job);
            $this->publishProgress($job);
        }
    }

    /**
     * Check the live DB status without interfering with the ORM identity map.
     */
    private function isJobCancelled(string $jobId): bool
    {
        $status = $this->entityManager->getConnection()->fetchOne(
            'SELECT status FROM crosswalk_job WHERE id = :id',
            ['id' => $jobId],
        );

        return 'cancelled' === $status;
    }

    private function publishProgress(CrosswalkJob $job): void
    {
        $this->hub->publish(new Update(
            "crosswalk-progress/{$job->id}",
            json_encode([
                'jobId' => (string) $job->id,
                'status' => $job->status,
                'progress' => [
                    'total' => $job->totalItems,
                    'processed' => $job->processedItems,
                    'matched' => $job->matchedItems,
                    'exact_match_items' => $job->exactMatchItems,
                    'related_items' => $job->relatedItems,
                    'skipped_no_embedding' => $job->skippedNoEmbedding,
                    'skipped_below_threshold' => $job->skippedBelowThreshold,
                    'failed' => $job->failedItems,
                ],
            ]),
        ));
    }
}
