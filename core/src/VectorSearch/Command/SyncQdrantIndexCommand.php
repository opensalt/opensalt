<?php

declare(strict_types=1);

namespace App\VectorSearch\Command;

use App\VectorSearch\Store\QdrantVectorStore;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'vector:qdrant:sync',
    description: 'Sync existing MySQL embeddings into the Qdrant index'
)]
class SyncQdrantIndexCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly QdrantVectorStore $qdrantVectorStore,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Number of embeddings to sync per batch', 500)
            ->addOption('after-id', null, InputOption::VALUE_OPTIONAL, 'Resume syncing after this LsItem ID', 0)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Optional maximum number of embeddings to sync', 0)
            ->setHelp(
                <<<'EOF'
The <info>vector:qdrant:sync</info> command copies existing embeddings from MySQL metadata storage into Qdrant.

Examples:
  <info>php bin/console vector:qdrant:sync</info>
  <info>php bin/console vector:qdrant:sync --batch-size=1000</info>
  <info>php bin/console vector:qdrant:sync --after-id=250000</info>
  <info>php bin/console vector:qdrant:sync --limit=10000</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $afterId = max(0, (int) $input->getOption('after-id'));
        $limit = max(0, (int) $input->getOption('limit'));

        $io->title('Qdrant Sync');
        $io->text(sprintf('Starting sync after LsItem ID %d with batch size %d.', $afterId, $batchSize));
        if ($limit > 0) {
            $io->text(sprintf('Sync limit set to %d embeddings.', $limit));
        }

        $synced = 0;
        $lastLsItemId = $afterId;

        while (true) {
            $remaining = $limit > 0 ? max(0, $limit - $synced) : null;
            if (null !== $remaining && 0 === $remaining) {
                break;
            }

            $rows = $this->fetchBatch($lastLsItemId, min($batchSize, $remaining ?? $batchSize));
            if ([] === $rows) {
                break;
            }

            $preparedRows = [];
            foreach ($rows as $row) {
                $decodedVector = json_decode((string) $row['vector'], true);
                if (!is_array($decodedVector)) {
                    throw new \RuntimeException(sprintf('Failed to decode vector JSON for LsItem %d.', (int) $row['ls_item_id']));
                }

                $preparedRows[] = [
                    'lsItemId' => (int) $row['ls_item_id'],
                    'frameworkId' => (int) $row['framework_id'],
                    'kind' => (int) $row['kind'],
                    'text' => (string) ($row['text'] ?? ''),
                    'isLeafNode' => (bool) $row['is_leaf_node'],
                    'sourceHierarchyUpdatedAt' => null !== $row['source_hierarchy_updated_at']
                        ? new \DateTimeImmutable((string) $row['source_hierarchy_updated_at'])
                        : null,
                    'vector' => array_map(static fn (mixed $value): float => (float) $value, $decodedVector),
                ];
            }

            $batchSynced = $this->qdrantVectorStore->importEmbeddings($preparedRows);
            $synced += $batchSynced;
            $lastLsItemId = (int) $rows[array_key_last($rows)]['ls_item_id'];

            $io->text(sprintf('Synced %d embeddings so far. Last LsItem ID: %d.', $synced, $lastLsItemId));
        }

        $io->success(sprintf('Qdrant sync complete. Synced %d embeddings. Last LsItem ID: %d.', $synced, $lastLsItemId));

        return Command::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchBatch(int $afterId, int $batchSize): array
    {
        $sql = <<<'SQL'
            SELECT
                embedding.ls_item_id,
                embedding.text,
                embedding.is_leaf_node,
                embedding.source_hierarchy_updated_at,
                embedding.vector,
                item.ls_doc_id AS framework_id,
                item.discriminator AS kind
            FROM ls_item_embedding embedding
            INNER JOIN ls_item item
                ON item.id = embedding.ls_item_id
            WHERE embedding.vector IS NOT NULL
              AND embedding.normalized_vector IS NOT NULL
              AND embedding.binary_code IS NOT NULL
              AND embedding.ls_item_id > :afterId
            ORDER BY embedding.ls_item_id ASC
            LIMIT :batchSize
        SQL;

        return $this->connection->fetchAllAssociative(
            $sql,
            [
                'afterId' => $afterId,
                'batchSize' => $batchSize,
            ],
            [
                'afterId' => ParameterType::INTEGER,
                'batchSize' => ParameterType::INTEGER,
            ]
        );
    }
}
