<?php

declare(strict_types=1);

namespace App\Crosswalk\MessageHandler;

use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateCrosswalkMessageHandler
{
    public function __construct(
        private CrosswalkJobRepository $jobRepository,
        private CrosswalkService $crosswalkService,
        private EntityManagerInterface $entityManager,
        private HubInterface $hub,
    ) {
    }

    public function __invoke(CreateCrosswalkMessage $message): void
    {
        $job = $this->jobRepository->find($message->jobId);
        if (null === $job) {
            return;
        }

        $sourceItems = $this->entityManager->getRepository(LsItem::class)->findBy([
            'lsDoc' => $message->originFrameworkId,
        ]);

        $job->markStarted(\count($sourceItems));
        $this->jobRepository->save($job);

        $batchSize = 50;
        $batchCount = 0;

        foreach ($sourceItems as $sourceItem) {
            $match = $this->crosswalkService->findBestMatch(
                $sourceItem,
                $message->destinationFrameworkId,
                $message->threshold,
            );

            if (null === $match) {
                $job->recordItemNoEmbedding();
                ++$batchCount;
                if (0 === $batchCount % $batchSize) {
                    $this->flushBatch($job, $message);
                    if ('cancelled' === $job->status) {
                        return;
                    }
                }
                continue;
            }

            $result = $this->crosswalkService->processItem(
                $sourceItem,
                $match['lsItem'],
                $match['similarity'],
                $message->exactMatchThreshold,
                $message->crosswalkFrameworkId,
                $message->threshold,
                $message->jobId,
            );

            if (CrosswalkService::RESULT_CREATED_EXACT === $result) {
                $job->recordItemProcessed($match['similarity'], true);
            } elseif (CrosswalkService::RESULT_CREATED_RELATED === $result) {
                $job->recordItemProcessed($match['similarity'], false);
            } else {
                $job->recordItemBelowThreshold();
            }

            ++$batchCount;
            if (0 === $batchCount % $batchSize) {
                $this->flushBatch($job, $message);
                if ('cancelled' === $job->status) {
                    return;
                }
            }
        }

        $this->entityManager->flush();
        $job->markCompleted();
        $this->jobRepository->save($job);
        $this->publishProgress($job);
    }

    private function flushBatch(CrosswalkJob &$job, CreateCrosswalkMessage $message): void
    {
        $this->publishProgress($job);
        $this->jobRepository->save($job);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $freshJob = $this->jobRepository->find($message->jobId);
        if ($freshJob && 'cancelled' === $freshJob->status) {
            $job = $freshJob;

            return;
        }
        $job = $freshJob ?? $job;
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
