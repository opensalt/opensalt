<?php

declare(strict_types=1);

namespace App\VectorSearch\MessageHandler;

use App\Entity\Framework\LsItem;
use App\VectorSearch\Message\GenerateEmbeddingMessage;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GenerateEmbeddingMessageHandler
{
    public function __construct(
        private VectorSearchService $vectorSearchService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GenerateEmbeddingMessage $message): void
    {
        $lsItemId = $message->getLsItemId();

        $this->logger->info('Processing GenerateEmbeddingMessage', [
            'ls_item_id' => $lsItemId,
        ]);

        try {
            // Fetch LsItem
            $lsItem = $this->entityManager->find(LsItem::class, $lsItemId);

            if (null === $lsItem) {
                $this->logger->warning('LsItem not found', [
                    'ls_item_id' => $lsItemId,
                ]);

                return;
            }

            // Check if embedding already exists (unless force is true)
            if (!$message->isForce() && $this->vectorSearchService->hasEmbedding($lsItem)) {
                $this->logger->info('Embedding already exists, skipping', [
                    'ls_item_id' => $lsItemId,
                ]);

                return;
            }

            // Generate and store embedding
            $embedding = $this->vectorSearchService->generateAndStoreEmbedding(
                $lsItem,
                $message->getText()
            );

            $this->logger->info('Embedding generated successfully', [
                'ls_item_id' => $lsItemId,
                'embedding_id' => $embedding->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error processing GenerateEmbeddingMessage', [
                'ls_item_id' => $lsItemId,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to allow retry logic
            throw $e;
        }
    }
}
