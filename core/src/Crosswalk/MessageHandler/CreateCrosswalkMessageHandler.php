<?php

declare(strict_types=1);

namespace App\Crosswalk\MessageHandler;

use App\Crosswalk\Entity\CrosswalkJob;
use App\Crosswalk\Message\CreateCrosswalkMessage;
use App\Crosswalk\Message\ProcessCrosswalkBatchMessage;
use App\Crosswalk\Repository\CrosswalkJobRepository;
use App\Crosswalk\Service\CrosswalkService;
use App\Entity\Framework\LsItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class CreateCrosswalkMessageHandler
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private CrosswalkJobRepository $jobRepository,
        private CrosswalkService $crosswalkService,
        private EntityManagerInterface $entityManager,
        private HubInterface $hub,
        private MessageBusInterface $messageBus,
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

        if ($message->originLeafOnly) {
            $leafIds = array_flip($this->crosswalkService->getLeafItemIds($message->originFrameworkId));
            $sourceItems = array_filter(
                $sourceItems,
                static fn (LsItem $item): bool => null !== $item->getId() && isset($leafIds[$item->getId()]),
            );
        }

        $itemCount = \count($sourceItems);
        $job->markStarted($itemCount);
        $this->jobRepository->save($job);
        $this->publishProgress($job);

        if (0 === $itemCount) {
            $job->markCompleted();
            $this->jobRepository->save($job);
            $this->publishProgress($job);

            return;
        }

        $itemIds = array_map(
            static fn (LsItem $item): int => (int) $item->getId(),
            array_values($sourceItems),
        );

        foreach (array_chunk($itemIds, self::BATCH_SIZE) as $batchIds) {
            $this->messageBus->dispatch(new ProcessCrosswalkBatchMessage(
                jobId: $message->jobId,
                itemIds: $batchIds,
                destinationLeafOnly: $message->destinationLeafOnly,
            ));
        }
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
