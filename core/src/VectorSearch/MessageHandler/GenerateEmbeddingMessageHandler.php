<?php

declare(strict_types=1);

namespace App\VectorSearch\MessageHandler;

use App\Entity\Framework\LsItem;
use App\VectorSearch\Message\GenerateEmbeddingMessage;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

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
            $lsItem = $this->entityManager->find(LsItem::class, $lsItemId);

            if (null === $lsItem) {
                $this->logger->warning('LsItem not found', [
                    'ls_item_id' => $lsItemId,
                ]);

                return;
            }

            $frameworkId = $lsItem->getLsDoc()->getId();
            if (null === $frameworkId) {
                $this->logger->warning('LsItem has no framework', [
                    'ls_item_id' => $lsItemId,
                ]);

                return;
            }

            $force = $message->isForce();
            if (!$force && $this->vectorSearchService->hasEmbedding($lsItem)) {
                $this->logger->info('Embedding already exists, skipping', [
                    'ls_item_id' => $lsItemId,
                ]);

                return;
            }

            $this->vectorSearchService->generateAndStoreEmbeddingsForFramework(
                $frameworkId,
                [$lsItemId],
                $force
            );

            $this->logger->info('Embedding generated successfully', [
                'ls_item_id' => $lsItemId,
            ]);
        } catch (UnrecoverableMessageHandlingException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->warning('Embedding generation failed (will retry)', [
                'ls_item_id' => $lsItemId,
                'error' => $e->getMessage(),
            ]);

            $previous = $e->getPrevious();
            if ($previous instanceof ClientException) {
                throw new UnrecoverableMessageHandlingException('Embedding request failed with client error: ' . $previous->getMessage(), 0, $previous);
            }

            throw $e;
        }
    }
}
