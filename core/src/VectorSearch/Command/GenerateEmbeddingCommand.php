<?php

declare(strict_types=1);

namespace App\VectorSearch\Command;

use App\Entity\Framework\LsItem;
use App\VectorSearch\Service\VectorSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'vector:generate-embeddings', description: 'Generate vector embeddings for LsItems', help: <<<'TXT'
The <info>vector:generate-embeddings</info> command generates embeddings for LsItems.

Examples:
  <info>php bin/console vector:generate-embeddings</info>
  <info>php bin/console vector:generate-embeddings 500</info>
  <info>php bin/console vector:generate-embeddings --reset-progress</info>
  <info>php bin/console vector:generate-embeddings --offset=1000 --limit=500</info>
  <info>php bin/console vector:generate-embeddings --ls-item-id=123</info>
  <info>php bin/console vector:generate-embeddings --force</info>
  <info>php bin/console vector:generate-embeddings --batch-size=100</info>
TXT)]
class GenerateEmbeddingCommand
{
    public function __construct(
        private readonly VectorSearchService $vectorSearchService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Limit number of items to process')] int $limit = 100,
        #[Option(description: 'Offset for batch processing', shortcut: 'o')] int $offset = 0,
        #[Option(description: 'Process specific LsItem by ID', name: 'ls-item-id', shortcut: 'i')] ?string $lsItemId = null,
        #[Option(description: 'Force regeneration of existing embeddings', shortcut: 'f')] bool $force = false,
        #[Option(description: 'Reset the automatic incremental cursor', name: 'reset-progress')] bool $resetProgress = false,
        #[Option(description: 'Batch size for processing', name: 'batch-size', shortcut: 'b')] int $batchSize = 50,
    ): int {
        ini_set('memory_limit', '1024M');

        $batchSize = max(1, $batchSize);
        $useCursor = null === $lsItemId && !$force && 0 === $offset;

        $io->title('Vector Embedding Generation');

        if ($resetProgress) {
            if ($useCursor) {
                $this->vectorSearchService->resetEmbeddingGenerationCursor();
            } else {
                $io->warning('--reset-progress only applies to the default incremental mode (no --force, no --offset, no --ls-item-id).');
            }
        }

        $cursorBefore = $useCursor ? $this->vectorSearchService->getEmbeddingGenerationCursor() : null;
        $requestedItems = $useCursor
            ? $this->getRequestedItemsFromCursor($limit, $cursorBefore)
            : $this->getRequestedItems($limit, $offset, $lsItemId);
        if ([] === $requestedItems) {
            $io->warning('No LsItems found to process.');

            return Command::SUCCESS;
        }

        $total = count($requestedItems);
        $frameworkGroups = $this->groupRequestedItemsByFramework($requestedItems);
        $cursorAfter = $useCursor ? $requestedItems[array_key_last($requestedItems)]['id'] : null;

        if (null !== $lsItemId) {
            $io->text(sprintf('Processing specific LsItem ID: %s', $lsItemId));
        } elseif ($useCursor) {
            $io->text(sprintf(
                'Scanning the next %d items after cursor %s.',
                $limit,
                null !== $cursorBefore ? (string) $cursorBefore : 'start'
            ));
        } else {
            $io->text(sprintf('Processing up to %d items starting from offset %d', $limit, $offset));
        }
        $io->text(sprintf('Found %d requested LsItems.', $total));
        $io->newLine();

        $progressBar = $io->createProgressBar($total);
        $progressBar->start();

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($frameworkGroups as $frameworkId => $frameworkItemIds) {
            try {
                $stats = $this->vectorSearchService->generateAndStoreEmbeddingsForFramework(
                    $frameworkId,
                    $frameworkItemIds,
                    $force,
                    $batchSize
                );

                $processed += $stats['processed'];
                $skipped += $stats['skipped'];
                $errors += $stats['missing'];
            } catch (\Throwable $exception) {
                $errors += count($frameworkItemIds);
                $io->newLine();
                $io->error(sprintf(
                    'Error processing framework %d: %s',
                    $frameworkId,
                    $exception->getMessage()
                ));
            } finally {
                $progressBar->advance(count($frameworkItemIds));
                $this->entityManager->clear();
            }
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->success('Embedding generation completed!');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total items', $total],
                ['Processed', $processed],
                ['Skipped (already current)', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($useCursor) {
            $this->vectorSearchService->saveEmbeddingGenerationCursor($cursorAfter);
            $io->note(sprintf(
                'Incremental cursor advanced from %s to %s. Use --reset-progress to start scanning from the beginning again.',
                null !== $cursorBefore ? (string) $cursorBefore : 'start',
                null !== $cursorAfter ? (string) $cursorAfter : 'start'
            ));
        }

        if (!$force && $skipped > 0) {
            $io->note('Use --force to rebuild existing embeddings with refreshed ancestor context and leaf-node metadata.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<array{id: int, frameworkId: int}>
     */
    private function getRequestedItems(int $limit, int $offset, mixed $lsItemId): array
    {
        $queryBuilder = $this->entityManager->getRepository(LsItem::class)->createQueryBuilder('li');
        $queryBuilder
            ->select('li.id AS id', 'IDENTITY(li.lsDoc) AS frameworkId')
            ->orderBy('frameworkId', 'ASC')
            ->addOrderBy('li.id', 'ASC');

        if (null !== $lsItemId) {
            $queryBuilder
                ->where('li.id = :id')
                ->setParameter('id', (int) $lsItemId);
        } else {
            $queryBuilder
                ->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        /** @var list<array{id: numeric-string|int, frameworkId: numeric-string|int}> $rows */
        $rows = $queryBuilder->getQuery()->getScalarResult();

        $requestedItems = [];
        foreach ($rows as $row) {
            $requestedItems[] = [
                'id' => (int) $row['id'],
                'frameworkId' => (int) $row['frameworkId'],
            ];
        }

        return $requestedItems;
    }

    /**
     * @return list<array{id: int, frameworkId: int}>
     */
    private function getRequestedItemsFromCursor(int $limit, ?int $cursor): array
    {
        $requestedItems = $this->getRequestedItemsAfterId($limit, $cursor);
        if (count($requestedItems) >= $limit || null === $cursor) {
            return $requestedItems;
        }

        $remaining = $limit - count($requestedItems);

        return [
            ...$requestedItems,
            ...$this->getRequestedItemsBeforeOrAtId($remaining, $cursor),
        ];
    }

    /**
     * @return list<array{id: int, frameworkId: int}>
     */
    private function getRequestedItemsAfterId(int $limit, ?int $cursor): array
    {
        $queryBuilder = $this->entityManager->getRepository(LsItem::class)->createQueryBuilder('li');
        $queryBuilder
            ->select('li.id AS id', 'IDENTITY(li.lsDoc) AS frameworkId')
            ->orderBy('li.id', 'ASC')
            ->setMaxResults($limit);

        if (null !== $cursor) {
            $queryBuilder
                ->where('li.id > :cursor')
                ->setParameter('cursor', $cursor);
        }

        /** @var list<array{id: numeric-string|int, frameworkId: numeric-string|int}> $rows */
        $rows = $queryBuilder->getQuery()->getScalarResult();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'frameworkId' => (int) $row['frameworkId'],
            ],
            $rows
        );
    }

    /**
     * @return list<array{id: int, frameworkId: int}>
     */
    private function getRequestedItemsBeforeOrAtId(int $limit, int $cursor): array
    {
        $queryBuilder = $this->entityManager->getRepository(LsItem::class)->createQueryBuilder('li');
        $queryBuilder
            ->select('li.id AS id', 'IDENTITY(li.lsDoc) AS frameworkId')
            ->where('li.id <= :cursor')
            ->setParameter('cursor', $cursor)
            ->orderBy('li.id', 'ASC')
            ->setMaxResults($limit);

        /** @var list<array{id: numeric-string|int, frameworkId: numeric-string|int}> $rows */
        $rows = $queryBuilder->getQuery()->getScalarResult();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'frameworkId' => (int) $row['frameworkId'],
            ],
            $rows
        );
    }

    /**
     * @param list<array{id: int, frameworkId: int}> $requestedItems
     * @return array<int, list<int>>
     */
    private function groupRequestedItemsByFramework(array $requestedItems): array
    {
        $frameworkGroups = [];
        foreach ($requestedItems as $requestedItem) {
            $frameworkGroups[$requestedItem['frameworkId']] ??= [];
            $frameworkGroups[$requestedItem['frameworkId']][] = $requestedItem['id'];
        }

        return $frameworkGroups;
    }
}
