<?php

declare(strict_types=1);

namespace App\Crosswalk\MessageHandler;

use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsItem;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateCrosswalkMessageHandler
{
    public function __construct(
        private CrosswalkJobRepository $jobRepository,
        private VectorSearchService $vectorSearchService,
        private CrosswalkService $crosswalkService,
        private EntityManagerInterface $entityManager,
        private HubInterface $hub,
    ) {
    }

    public function __invoke(CreateCrosswalkMessage $message): void
    {
        $job = $this->jobRepository->find($message->getJobId());
        if (null === $job) {
            return;
        }

        $sourceItems = $this->entityManager->getRepository(LsItem::class)->findBy([
            'lsDoc' => $message->getOriginFrameworkId(),
        ]);

        $job->markStarted(\count($sourceItems));
        $this->jobRepository->save($job);

        $batchSize = 50;
        $batchCount = 0;

        foreach ($sourceItems as $sourceItem) {
            $match = $this->crosswalkService->findBestMatch(
                $sourceItem,
                $message->getDestinationFrameworkId(),
                $message->getThreshold(),
            );

            if (null === $match) {
                $job->recordItemSkipped();
                continue;
            }

            $result = $this->crosswalkService->processItem(
                $sourceItem,
                $match['lsItem'],
                $match['similarity'],
                $message->getExactMatchThreshold(),
                $message->getCrosswalkFrameworkId(),
            );

            if ($result === CrosswalkService::RESULT_CREATED_EXACT) {
                $job->recordItemProcessed($match['similarity'], true);
            } elseif ($result === CrosswalkService::RESULT_CREATED_RELATED) {
                $job->recordItemProcessed($match['similarity'], false);
            } else {
                $job->recordItemSkipped();
            }

            ++$batchCount;
            if ($batchCount % $batchSize === 0) {
                $this->publishProgress($job);
                $this->jobRepository->save($job);
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $job->markCompleted();
        $this->jobRepository->save($job);
        $this->publishProgress($job);
    }

    private function publishProgress($job): void
    {
        $this->hub->publish(new Update(
            "crosswalk-progress/{$job->getId()}",
            json_encode([
                'jobId' => $job->getId(),
                'status' => $job->getStatus(),
                'progress' => [
                    'total' => $job->getTotalItems(),
                    'processed' => $job->getProcessedItems(),
                    'matched' => $job->getMatchedItems(),
                    'exact_match_items' => $job->getExactMatchItems(),
                    'related_items' => $job->getRelatedItems(),
                    'skipped' => $job->getSkippedNoEmbedding(),
                    'failed' => $job->getFailedItems(),
                ],
            ]),
        ));
    }
}
