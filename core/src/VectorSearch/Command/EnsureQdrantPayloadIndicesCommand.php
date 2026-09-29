<?php

declare(strict_types=1);

namespace App\VectorSearch\Command;

use App\VectorSearch\Store\HybridQdrantStore;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'vector:qdrant:ensure-indices', description: 'Ensure Qdrant payload indices exist on the active (or a given) collection', help: <<<'TXT'
The <info>vector:qdrant:ensure-indices</info> command ensures that the payload indices used for
filtered searches and exact counts exist on the active (or a given) Qdrant collection.

This is idempotent and safe to run at any time. It is primarily used to backfill indices on
collections that were created before payload indices were configured, or after importing data.

Examples:
  <info>php bin/console vector:qdrant:ensure-indices</info>
  <info>php bin/console vector:qdrant:ensure-indices --wait</info>
  <info>php bin/console vector:qdrant:ensure-indices ls_item_embeddings__rebuild__manual</info>
TXT)]
class EnsureQdrantPayloadIndicesCommand
{
    public function __construct(
        private readonly HybridQdrantStore $qdrantStore,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Collection name; defaults to the active collection')] string $collection = '',
        #[Option(description: 'Poll until the indices report as fully built before exiting', shortcut: 'w')] bool $wait = false,
    ): int {
        $collection = trim($collection);

        $target = '' !== $collection ? $collection : ($this->qdrantStore->getResolvedActiveCollectionName() ?? $this->qdrantStore->getActiveCollectionReference());

        if ('' === $target) {
            $io->error('No active collection could be resolved. Pass a collection name as an argument.');

            return Command::FAILURE;
        }

        $io->title('Qdrant Payload Indices');
        $io->text(sprintf('Target collection: %s', $target));

        $ensured = $this->qdrantStore->ensurePayloadIndices($target);
        $io->success(sprintf('Requested payload indices for: %s', implode(', ', $ensured)));

        if ($wait) {
            $this->awaitIndices($io, $target, $ensured);
        } else {
            $io->note('Index creation is asynchronous; large collections may take a while to finish building. Re-run with --wait to poll until ready.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $fields
     */
    private function awaitIndices(SymfonyStyle $io, string $collection, array $fields): void
    {
        $expected = count($fields);
        $deadline = time() + 120;
        $io->text('Waiting for indices to report as built...');

        while (time() < $deadline) {
            $built = $this->qdrantStore->countIndexedPayloadFields($collection);
            if ($built >= $expected) {
                $io->success(sprintf('All %d payload indices are built.', $expected));

                return;
            }

            $io->text(sprintf('  built %d/%d indices...', $built, $expected));
            sleep(2);
        }

        $io->warning('Timed out waiting for indices to finish building. They will continue building in the background.');
    }
}
